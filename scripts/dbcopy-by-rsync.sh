# remember to run as root!
# config 

if ! [ $(id -u) = 0 ]; then
   echo "I am not root!"
   exit 1
fi

if [ ! "$3" ]
then
    echo "Usage: '"$(basename "$0") "sourcedb targetdb dumpfile'"
    exit
fi

sourcedb="$1"
targetdb="$2"
dumpfile="$3"

# for debug; print marker and pause after each step; also make extra copies of files
# a delay gives you time to watch the mysql error log and associate errors with cause
# (which can be suprisingly difficult - some are a bit "computer says no")
delay=0

# table and views both work
input_tables=("paysuite_collection" "paysuite_mandate" "rsm_collection" "rsm_mandate" "sterling_ticket" "stripe_payment")
blotto_tables=("blotto_build_collection" "blotto_build_mandate" "blotto_change" "blotto_contact")
blotto_tables+=("blotto_entry" "blotto_external" "blotto_fee" "blotto_generation" "blotto_insurance" "blotto_player")
blotto_tables+=("blotto_prize" "blotto_result")
blotto_tables+=("blotto_supporter" "blotto_update" "blotto_verification" "blotto_winner")
out_tables=("ANLs" "BenchmarkNoShows" "Cancellations" "Changes" "Draws" "Draws_Summary" "Draws_Supersummary" "Insurance" "Insurance_Summary")
out_tables+=("Journeys" "JourneysDormancy" "JourneysMonthly" "Monies" "MoniesMonthly" "MoniesWeekly")
out_tables+=("Supporters" "SupportersView" "Updates" "UpdatesLatest" "Wins" "WinsAdmin" "WinsForWise")
# if we get more than two bespoke tables we can think about configuration 
out_tables+=("Updates_SHC")


# end config

echo "copy database by rsync"

# source: build table lists for export and re-indexing from candidate tables above
myi_tables=()
ibd_tables=()
frm_views=()
fst_strings=()
tables=(${input_tables[@]} ${blotto_tables[@]} ${out_tables[@]})
for table in ${tables[@]}
do
  table_exists=false
  if [ -f /var/lib/mysql/${sourcedb}/${table}.ibd ]; then
    ibd_tables+=("$table")
    table_exists=true
  elif [ -f /var/lib/mysql/${sourcedb}/${table}.MYI ]; then
    myi_tables+=("$table")
    table_exists=true
  elif [ -f /var/lib/mysql/${sourcedb}/${table}.frm ]; then
    frm_views+=("$table")
  fi
  # probably only needs to be done for ibd_tables
  if [ "$table_exists" = true ] ; then
    # echo "found $table"
    tdef=`mariadb ${sourcedb} <<< "SHOW CREATE TABLE ${table}"`
    fst_lines=`echo -e $tdef | grep FULLTEXT` # -e to turn \n into newline
    if [ ! -z "$fst_lines" ] ; then
      #echo "$fst_lines"  
      while read -r fst_line ; do
        fst_index=$(echo "$fst_line" | awk '{ print $3}')
        fst_keys=$(echo "$fst_line" | awk -F "[()]" '{ print $2}')
        fst_strings+=("$table $fst_index $fst_keys")
        #echo "$fst_line"
      done <<< "$fst_lines"
    fi
  fi
done

# source: dump table and view definitions
schema=(${ibd_tables[@]} ${myi_tables[@]} ${frm_views[@]})

mysqldump --no-data --routines --events ${sourcedb} ${schema[@]} > ${dumpfile}
if (( $delay > 0 )) ; then echo "a"; sleep $delay; fi

# target: drop and create database
mariadb <<< "DROP DATABASE IF EXISTS ${targetdb}"
mariadb <<< "CREATE DATABASE ${targetdb}"

# target: recreate tables
mariadb ${targetdb} < ${dumpfile}
if (( $delay > 0 )) ; then echo "b"; sleep $delay; fi

# target: discard tablespace (note - actually deletes .ibd file)
# "If the table has a foreign key relationship, foreign_key_checks must be disabled before executing DISCARD TABLESPACE. ""
for table in ${ibd_tables[@]}
do
    mariadb ${targetdb} <<< "SET FOREIGN_KEY_CHECKS=0; ALTER TABLE ${table} DISCARD TABLESPACE"
done
if (( $delay > 0 )) ; then echo "c"; sleep $delay; fi

# source: flush for export
# comma separated list of tables (not views)
table_list=`echo ${ibd_tables[@]} ${myi_tables[@]} | tr ' ' ','`
# https://stackoverflow.com/questions/3463106/how-to-keep-a-mysql-connection-open-in-bash
# https://dev.mysql.com/doc/refman/8.0/en/innodb-table-import.html: connection must remain open, otherwise, the .cfg file is removed 
# TODO race condition - we need to wait for the flush tables to finish before copying files.
# see at end https://stackoverflow.com/questions/3463106/how-to-keep-a-mysql-connection-open-in-bash
# uses background process not coproc
coproc mariadb ${sourcedb}; 
#echo "flush tables ${table_list}"
echo "FLUSH TABLES ${table_list} FOR EXPORT;" >&"${COPROC[1]}"
echo "create (anti) lockfile /tmp/$$.antilock"
echo "SELECT 'foo' INTO OUTFILE '/tmp/$$.antilock';" >&"${COPROC[1]}"

counter=0
while [ ! -f "/tmp/$$.antilock" ]
do
  ((counter++))
  if [ $counter -gt 12 ] ; then 
    echo "quit - flush tables timeout"
    exit
  fi
  #echo -n "."
  sleep  1
done
echo "counter $counter"
rm "/tmp/$$.antilock"


if (( $delay > 0 )) ; then echo "d"; sleep $delay; fi

# copy ibd and cfg files
for table in ${ibd_tables[@]}
do
    rsync -a /var/lib/mysql/${sourcedb}/${table}.ibd /var/lib/mysql/${targetdb}/
    rsync -a /var/lib/mysql/${sourcedb}/${table}.cfg /var/lib/mysql/${targetdb}/
    rsync -a /var/lib/mysql/${sourcedb}/${table}.frm /var/lib/mysql/${targetdb}/
    if (( $delay > 0 )) ; then 
        rsync -a /var/lib/mysql/${sourcedb}/${table}.ibd /home/dom/tmp/
        rsync -a /var/lib/mysql/${sourcedb}/${table}.cfg /home/dom/tmp/
        rsync -a /var/lib/mysql/${sourcedb}/${table}.frm /home/dom/tmp/
    fi
done
if (( $delay > 0 )) ; then echo "e"; sleep $delay; fi

# copy myisam files
for table in ${myi_tables[@]}
do
    rsync -a /var/lib/mysql/${sourcedb}/${table}.MYI /var/lib/mysql/${targetdb}/
    rsync -a /var/lib/mysql/${sourcedb}/${table}.MYD /var/lib/mysql/${targetdb}/
    rsync -a /var/lib/mysql/${sourcedb}/${table}.frm /var/lib/mysql/${targetdb}/
    if (( $delay > 0 )) ; then 
        rsync -a /var/lib/mysql/${sourcedb}/${table}.MYI /home/dom/tmp/
        rsync -a /var/lib/mysql/${sourcedb}/${table}.MYD /home/dom/tmp/
        rsync -a /var/lib/mysql/${sourcedb}/${table}.frm /home/dom/tmp/
    fi
done
if (( $delay > 0 )) ; then echo "f"; sleep $delay; fi

# source: unlock (probably not needed as when script dies connection closes and tables unlocked)
echo "UNLOCK TABLES;" >&"${COPROC[1]}"
if (( $delay > 0 )) ; then echo "g"; sleep $delay; fi

# target: import tablespace
for table in ${ibd_tables[@]}
do
    #echo "ALTER TABLE ${table} IMPORT TABLESPACE"
    mariadb ${targetdb} <<< "ALTER TABLE ${table} IMPORT TABLESPACE"
done

# target: rebuild full text indexes
for fst in "${fst_strings[@]}"
do  
    elems=()
    for i in $fst; do elems+=($i) ; done
    # ${elems[0]} - table; ${elems[1]} - index (with backticks);  ${elems[2]}- columns
    # DROP INDEX [IF EXISTS] index_name ON tbl_name 
    #echo "DROP INDEX IF EXISTS ${elems[1]} ON \`${elems[0]}\`"
    mariadb ${targetdb} <<< "DROP INDEX IF EXISTS ${elems[1]} ON \`${elems[0]}\`"
    if (( $delay > 0 )) ; then echo "h"; sleep $delay; fi
    # CREATE OR REPLACE FULLTEXT INDEX index_name ON tbl_name (index_col_name,...)
    #echo "CREATE FULLTEXT INDEX ${elems[1]} ON \`${elems[0]}\` (${elems[2]})"
    mariadb ${targetdb} <<< "CREATE OR REPLACE FULLTEXT INDEX ${elems[1]} ON \`${elems[0]}\` (${elems[2]})"
    if (( $delay > 0 )) ; then echo "i"; sleep $delay; fi
done

echo "finished copying database"
