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

echo "copy database by dump"

sourcedb="$1"
targetdb="$2"
dumpfile="$3"

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

# source: build table lists for export and re-indexing from candidate tables above

myi_tables=()
ibd_tables=()
frm_views=()
tables=(${input_tables[@]} ${blotto_tables[@]} ${out_tables[@]})
#echo ${tables[@]}
for table in ${tables[@]}
do
  if [ -f /var/lib/mysql/${sourcedb}/${table}.ibd ]; then
    ibd_tables+=("$table")
  elif [ -f /var/lib/mysql/${sourcedb}/${table}.MYI ]; then
    myi_tables+=("$table")
  elif [ -f /var/lib/mysql/${sourcedb}/${table}.frm ]; then
    frm_views+=("$table")
  fi
done


# source: dump tables
schema=(${ibd_tables[@]} ${myi_tables[@]} ${frm_views[@]})
#echo "mysqldump --routines --events ${sourcedb} ${schema[@]} > ${dumpfile}"
mysqldump --routines --events ${sourcedb} ${schema[@]} > ${dumpfile}

# target: drop and create database
mariadb <<< "DROP DATABASE IF EXISTS ${targetdb}"
mariadb <<< "CREATE DATABASE ${targetdb}"

# target: import data
mariadb ${targetdb} < ${dumpfile}

