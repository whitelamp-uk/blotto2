

# Functions

abort_on_error () {
    if [ "$2" = "0" ]
    then
        return
    fi
    if [ "$3" ]
    then
        cat "$3"
    fi
    echo "Aborting at step $1 on error status = $2"
    if [ ! "$aux" ]
    then
        /usr/bin/php "$prm" "$cfg" "$brd build aborted at step $1 on error status = $2"
    fi
    rm -f $cfg.inhibit
    rm -f $ofl
    # For the benefit of a manual terminal
    echo -n $'\a'
    sleep 1
    echo -n $'\a'
    sleep 1
    echo -n $'\a'
    exit $2
}

finish_up () {
    rm -f $cfg.inhibit
    rm -f $ofl
    rm -f $tmp
    if [ "$bel" ]
    then
        echo -n $'\a'
    fi
    if [ ! "$aux" ]
    then
        /usr/bin/php "$prm" "$cfg" "$brd build completed successfully"
    fi
    echo ""
    echo "That's all folks!"
    echo ""
}

get_args () {
    aux=""
    sw=""
    clone=""
    manual=""
    no_tidy=""
    rehearse=""
    while [ 1 ]
    do
        if [ $# -lt 2 ]
        then
            return
        fi
        sws="$1"
        shift
        if [[ "$sws" == "-"* ]]
        then
            if    [[ "$sws" == *"c"* ]]
            then
                echo  "Option: clone from another DB"
                aux="1"
                clone="$1"
                shift
            fi

            if    [[ "$sws" == *"m"* ]]
            then
                echo  "Option: manual result insertion"
                aux="1"
                manual="1"
                draw_closed="$1"
                prize_group="$2"
                manual_number="$3"
                shift
                shift
                shift
            fi
            if    [[ "$sws" == *"n"* ]]
            then
                echo  "Option: no tidying"
                no_tidy="1"
                sw="$sw -n"
            fi
            if [[ "$sws" == *"r"* ]]
            then
                echo  "Option: rehearse only"
                rehearse="1"
                sw="$sw -r"
            fi
        fi
    done
}

get_cfg () {
    cfg=""
    while [ 1 ]
    do
        if [ $# -lt 2 ]
        then
            if [ "$args" ]
            then
                get_args $args
            fi
            cfg="$1"
            return
        fi
        args="$args $1"
        shift
    done
}

# User

if [ "$UID" != "0" ]
then
    echo "Must be run as root"
    exit 102
fi


# Arguments
get_cfg "$@"
if [ ! "$cfg" ]
then
    echo "/bin/bash $0 [-options] path_to_config_file"
    echo "  options can be combined eg. -nvrs"
    echo "    -c orig_db             clone static tables"
    echo "    -m draw_closed grp nr  manual insert @draw_closed for grp this nr"
    echo "    -n                     no tidying (leave behind temp files/DB tables)"
    echo "    -r                     rehearsal only (do not recreate front-end BLOTTO_DB)"
    echo "    -s                     single draw only (do next required draw and exit)"
    echo "    -v                     verbose (echo full log to STDOUT)"
    exit 103
fi

if [ ! -f "$cfg" ]
then
    echo "Cannot find config file \"$cfg\""
    exit 104
fi

# Check config for basic PHP errors
/usr/bin/php  "$cfg"
if [ $? != 0 ]
then
    echo "Config file \"$cfg\" is not usable"
    exit 105
fi


# Tidy

find . -iname 'blotto.*.tmp' -mtime +2 -type f -delete


# Definitions

drp="$(dirname "$0")"
brd="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_BRAND           )"
bel="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_BELL            )"
ldr="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_LOG_DIR         )"
dfl="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_DUMP_FILE       )"
sdr="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_CSV_DIR_S       )"
pdr="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_PROOF_DIR       )"
dbm="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_MAKE_DB         )"
dbo="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_DB              )"
dbt="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_TICKET_DB       )"
rbe="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_RBE_DBS         )"
usr="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_ORG_USER        )"
ofl="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_OUTFILE         )"
bpf="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_BESPOKE_SQL_FNC )"
bpu="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_BESPOKE_SQL_UPD )"
bpp="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_BESPOKE_SQL_PRM )"
mda="$( /usr/bin/php  "$drp/define.php"  "$cfg"  BLOTTO_MYSQLDUMP_AUTH  )"
nxt="$( /usr/bin/php  "$drp/exec.php"    "$cfg"  draw_upcoming          )"
tmp="$ldr/blotto.$$.tmp"
sps="$ldr/blotto.supporters.sql.last"
ddc="$ldr/blotto.directdebits_create.sql.last.log"
ddx="$ldr/blotto.directdebits_xform.sql.last.log"
pls="$ldr/blotto.players.sql.last.log"
plu="$ldr/blotto.player_updates.sql.last.log"
tks="$ldr/blotto.tickets.sql.last.log"
wns="$ldr/blotto.winnings.sql.last.log"
lgs="$ldr/blotto.legends.sql.last.log"
rhf="$pdr/result_history.csv"
prg="$drp/blotto.php"
prm="$(dirname $0)/mail.php"
echo "Temp file: $tmp"


# Processes
if [ "$manual" ]
then
    echo "MANUAL. Insert results"
    start=$SECONDS
echo /usr/bin/php $prg $sw "$cfg" exec manual.php $draw_closed $prize_group $manual_number
    /usr/bin/php $prg $sw "$cfg" exec manual.php $draw_closed $prize_group $manual_number
    abort_on_error MANUAL $?
    finish_up
    exit
fi


echo " 1. Create databases (if missing) $dbm and $dbt"
start=$SECONDS
/usr/bin/php $prg $sw "$cfg" sql db.create.sql          > $tmp
abort_on_error 1a $? $tmp
cat $tmp
mariadb                                                 < $tmp
abort_on_error 1b $?
echo "    Completed in $(($SECONDS-$start)) seconds"


if [ "$clone" ]
then
    echo "CLONE. From the origin database $clone"
    start=$SECONDS
    /usr/bin/php $prg $sw "$cfg" exec clone.php "$clone"
    abort_on_error CLONE $?
    finish_up
    exit
fi


echo " 2. Create/overwrite stored procedures"
start=$SECONDS
/usr/bin/php $prg $sw "$cfg" sql db.functions.sql       > $tmp
abort_on_error 2a $? $tmp
cat $tmp
mariadb                                                 < $tmp
abort_on_error 2b $?
/usr/bin/php $prg $sw "$cfg" sql db.routines.sql        > $tmp
abort_on_error 2c $? $tmp
cat $tmp
mariadb                                                 < $tmp
abort_on_error 2d $?
/usr/bin/php $prg $sw "$cfg" sql db.routines.admin.sql  > $tmp
abort_on_error 2e $? $tmp
cat $tmp
mariadb                                                 < $tmp
abort_on_error 2f $?
/usr/bin/php $prg $sw "$cfg" sql db.routines.org.sql    > $tmp
abort_on_error 2g $? $tmp
cat $tmp
mariadb                                                 < $tmp
abort_on_error 2h $?


echo " 2+. Bespoke SQL functions"
start=$SECONDS
mariadb $dbm                                            < $bpf
abort_on_error 2i $?
echo "    Completed in $(($SECONDS-$start)) seconds"


echo " 3. Generate mandate / collection table create SQL in $ddc"
start=$SECONDS
/usr/bin/php $prg $sw "$cfg" sql payment.create.sql > $ddc
abort_on_error 3 $?
echo "    Completed in $(($SECONDS-$start)) seconds"


echo " 4. Create mandate / collection tables using $ddc"
start=$SECONDS
mariadb                                             < $ddc
abort_on_error 4 $?
echo "    Completed in $(($SECONDS-$start)) seconds"


if [ "$rbe" = "" ]
then

    echo "5. Fetch mandate/collection data, purge bogons and spit out nice tables"
    start=$SECONDS
    /usr/bin/php $prg $sw "$cfg" exec payment_fetch.php
    abort_on_error 5 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"


    echo " 6. Generate mandate / collection table index / transform SQL in $ddx"
    start=$SECONDS
    /usr/bin/php $prg $sw "$cfg" sql payment.update.sql > $ddx
    abort_on_error 6 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"


    echo " 7. Index / transform mandate / collection tables using $ddx"
    start=$SECONDS
    mariadb                                             < $ddx
    abort_on_error 7 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"


    for dir in $(ls "$sdr")
    do

        if [ ! -d "$sdr/$dir" ]
        then
            continue
        fi

        echo " 8. Processing CCC directory $sdr/$dir"

        if [ ! "$(ls $sdr/$dir)" ]
        then
            echo "No file found in $sdr/$dir - skipping"
            continue
        fi

        echo "     9. Generate supporter temp table SQL for $sdr/$dir"
        start=$SECONDS
        /usr/bin/php $prg $sw "$cfg" sql import.supporter.sql "$sdr/$dir" > $tmp
        abort_on_error 9 $? $tmp
        cat $tmp
        echo "        Completed in $(($SECONDS-$start)) seconds"

        echo "    10. Create supporter temp table"
        start=$SECONDS
        mariadb                                             < $tmp
        abort_on_error 10 $?
        echo "        Completed in $(($SECONDS-$start)) seconds"

        echo "    11. Generate supporter insert SQL in $sps-$dir.log"
        start=$SECONDS
        /usr/bin/php $prg $sw "$cfg" exec supporters.php $dir > "$sps-$dir.log"
        abort_on_error 11a $?
        if [ "$no_tidy" ]
        then
            echo "        Renaming table tmp_supporter to tmp_supporter_$dir"
            mariadb $dbm                                  <<< "DROP TABLE IF EXISTS tmp_supporter_$dir;"
            abort_on_error 11b $?
            mariadb $dbm                                  <<< "RENAME TABLE tmp_supporter TO tmp_supporter_$dir;"
            abort_on_error 11c $?
        else
            echo "        Dropping table tmp_supporter"
            mariadb $dbm                                  <<< "DROP TABLE tmp_supporter;"
            abort_on_error 11d $?
        fi
        echo "        Completed in $(($SECONDS-$start)) seconds"

        echo "    12. Insert supporters from $sps-$dir.log"
        start=$SECONDS
        mariadb                                             < "$sps-$dir.log"
        abort_on_error 12 $?
        echo "        Completed in $(($SECONDS-$start)) seconds"

    done


    echo "13. Generate player insert SQL in $pls"
    start=$SECONDS
    /usr/bin/php $prg $sw "$cfg" exec players.php           > $pls
    abort_on_error 13 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"


    echo "14. Bind RSM data to player data using $pls"
    start=$SECONDS
    mariadb                                                 < $pls
    abort_on_error 14 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"


    echo "15. Generate player update SQL in $plu"
    start=$SECONDS
    /usr/bin/php $prg $sw "$cfg" exec players_update.php    > $plu
    abort_on_error 15 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"


    echo "16. Set player first draw dates and chances using $plu"
    start=$SECONDS
    mariadb                                                 < $plu
    abort_on_error 16 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"


    echo "17. Mangle data if a demo"
    start=$SECONDS
    /usr/bin/php $prg $sw "$cfg" exec demo.php
    abort_on_error 17 $?
    echo "    Completed in $(($SECONDS-$start)) seconds"

fi

echo "18. Generate ticket pool update SQL in $tks"
start=$SECONDS
/usr/bin/php $prg $sw "$cfg" exec tickets.php           > $tks
abort_on_error 18 $?
echo "    Completed in $(($SECONDS-$start)) seconds"

echo "19. Update ticket pool from $tks"
start=$SECONDS
mariadb                                                 < $tks
abort_on_error 19 $?
echo "    Completed in $(($SECONDS-$start)) seconds"

echo "20. Check for ticket discrepancies"
start=$SECONDS
/usr/bin/php $prg $sw "$cfg" exec ticket_discrepancy.php
abort_on_error 20 $?
echo "    Completed in $(($SECONDS-$start)) seconds"


start=$SECONDS
if [ "$rbe" = "" ]
then
    echo "21. Generate draw entries based on calculated balances"
    /usr/bin/php $prg $sw "$cfg" exec entries.php -q
    abort_on_error 21 $?
else
    echo "22. Generate draw entries from other organisations based on rules"
    /usr/bin/php $prg $sw "$cfg" exec entries.php -qr
    abort_on_error 22 $?
fi
echo "    Completed in $(($SECONDS-$start)) seconds"


start=$SECONDS
if [ "$rbe" = "" ]
then
    echo "23. Do draws with notarisation and insert winners"
    /usr/bin/php $prg $sw "$cfg" exec draws.php -q
    abort_on_error 23 $?
else
    echo "24. Do rule-based-entry draws with notarisation, insert winners and organisation-specific winners"
    /usr/bin/php $prg $sw "$cfg" exec draws.php -qr
    abort_on_error 24 $?
fi
echo "    Completed in $(($SECONDS-$start)) seconds"


echo " 25. Generate result history file at $rhf"
start=$SECONDS
/usr/bin/php $prg $sw $cfg sql results.export.sql       > $tmp
abort_on_error 25a $?
cat $tmp
rm -f "$ofl"
mariadb $dbm                                            < $tmp
abort_on_error 25b $?
rm -f "$rhf"
mv "$ofl" "$rhf"
echo "    Completed in $(($SECONDS-$start)) seconds"


echo "26. Build results tables in make database"
start=$SECONDS
echo "    CALL anls();"
mariadb $dbm                                      <<< "CALL anls();"
abort_on_error 26a $?
echo "    CALL cancellationsByRule();"
mariadb $dbm                                      <<< "CALL cancellationsByRule();"
abort_on_error 26b $?
echo "    CALL draws();"
mariadb $dbm                                      <<< "CALL draws();"
abort_on_error 26c $?
echo "    CALL drawsSummarise();"
mariadb $dbm                                      <<< "CALL drawsSummarise();"
abort_on_error 26d $?
if [ "$rbe" = "" ]
then
    echo "    CALL insure('$nxt');"
    mariadb $dbm                                  <<< "CALL insure('$nxt');"
    abort_on_error 26e $?
else
    echo "    CALL insureRBE();"
    mariadb $dbm                                  <<< "CALL insureRBE();"
    abort_on_error 26f $?
fi
echo "    CALL supporters();"
mariadb $dbm                                      <<< "CALL supporters();"
abort_on_error 26g $?
echo "    CALL updates();"
mariadb $dbm                                      <<< "CALL updates();"
abort_on_error 26h $?
echo "    CALL winners();"
mariadb $dbm                                      <<< "CALL winners();"
abort_on_error 26i $?
if [ -f "$bpu" ]
then
    echo "    Generate bespoke SQL"
    /usr/bin/php $prg $sw "$cfg" sql BESPOKE "$bpu"     > $tmp
    abort_on_error 26j $?
    echo "    Execute bespoke SQL in make database"
    mariadb                                             < $tmp
    abort_on_error 26k $?
fi
echo "    Completed in $(($SECONDS-$start)) seconds"


echo "26. [Interim] If appropriate, generate Zaffo superdraw entries (insurance format)"
start=$SECONDS
/usr/bin/php $prg $sw "$cfg" sql db.routines.superdraw.sql > $tmp
abort_on_error 26x $? $tmp
cat $tmp
mariadb                                                 < $tmp
abort_on_error 26y $?
/usr/bin/php $prg $sw "$cfg" exec superdraw_export.php
abort_on_error 26z $?
echo "    Completed in $(($SECONDS-$start)) seconds"


echo "27. Amend and build canvassing company change report"
start=$SECONDS
echo "    CALL changesGenerate();"
mariadb $dbm                                          <<< "CALL changesGenerate();"
abort_on_error 27a $?
/usr/bin/php $prg $sw "$cfg" exec changes.php -q
abort_on_error 27b $?
echo "    CALL changes();"
mariadb $dbm                                          <<< "CALL changes();"
abort_on_error 27c $?
echo "    Completed in $(($SECONDS-$start)) seconds"


echo "28. Generate preference column names in $lgs"
start=$SECONDS
/usr/bin/php $prg $sw "$cfg" exec legends.php           > $lgs
abort_on_error 28 $?
echo "    Completed in $(($SECONDS-$start)) seconds"


echo "29. Alter/drop preference columns in make database"
start=$SECONDS
mariadb                                                 < $lgs
abort_on_error 29 $?
echo "    Completed in $(($SECONDS-$start)) seconds"


echo "30. Generating dump file of make database at $dfl ..."
start=$SECONDS
if [ ! "$no_tidy" ]
then
    echo "    Dropping construction stored procedures, functions and temporary tables from make database"
    /usr/bin/php $prg $sw "$cfg" sql db.routines.drop.sql > $tmp
    abort_on_error 30a $? $tmp
    cat $tmp
    /usr/bin/php $prg $sw "$cfg" sql db.functions.drop.sql > $tmp
    abort_on_error 30b $? $tmp
    cat $tmp
    /usr/bin/php $prg $sw "$cfg" sql db.tables.drop.sql > $tmp
    abort_on_error 30c $? $tmp
    cat $tmp
    mariadb                                             < $tmp
    abort_on_error 30d $?
fi
echo mysqldump --defaults-extra-file=$mda --routines $dbm '>' $dfl
mysqldump --defaults-extra-file=$mda --routines $dbm    > $dfl
abort_on_error 30e $?
echo "    Completed in $(($SECONDS-$start)) seconds"


echo "31. Recreate organisation database"
if [ "$rehearse" ]
then
    echo "    Rehearsal only - skipping"
else
    touch $cfg.inhibit
    start=$SECONDS
    echo "    DROP DATABASE IF EXISTS $dbo;"
    mariadb                                           <<< "DROP DATABASE IF EXISTS $dbo;"
    abort_on_error 31a $?
    echo "    CREATE DATABASE $dbo COLLATE 'utf8_general_ci';"
    mariadb                                           <<< "CREATE DATABASE $dbo COLLATE 'utf8_general_ci';"
    abort_on_error 31b $?
    echo "    Importing from $dfl ..."
    mariadb $dbo                                        < $dfl
    abort_on_error 31c $?
    if [ ! "$no_tidy" ]
    then
        echo "    Deleting $dfl"
        rm $dfl
        abort_on_error 31d $?
    fi
    echo "    Completed in $(($SECONDS-$start)) seconds"
fi


echo "32. Grant organisation database permissions to admin user and organisation role"
if [ "$rehearse" ]
then
    echo "    Rehearsal only - skipping"
else
    start=$SECONDS
    /usr/bin/php $prg $sw "$cfg" sql db.permissions.sql > $tmp
    abort_on_error 32a $? $tmp
    cat $tmp
    mariadb                                             < $tmp
    abort_on_error 32b $?
    /usr/bin/php $prg $sw "$cfg" sql db.permissions.reports.sql > $tmp
    abort_on_error 31c $? $tmp
    cat $tmp
    mariadb                                             < $tmp
    abort_on_error 32d $?
    if [ -f "$bpp" ]
    then
        echo "    Grant bespoke permissions"
        /usr/bin/php $prg $sw "$cfg" sql BESPOKE "$bpp" > $tmp
        abort_on_error 32e $?
        mariadb                                         < $tmp
        abort_on_error 32f $?
    fi
    echo "    Completed in $(($SECONDS-$start)) seconds"
fi

if [ "$rbe" = "" ]
then

    echo "33. Cache slow front-end SQL"
    if [ "$rehearse" ]
    then
        echo "    Rehearsal only - skipping"
    else
        start=$SECONDS
        /usr/bin/php $prg $sw "$cfg" exec cache.php
        abort_on_error 33 $?
        echo "    Completed in $(($SECONDS-$start)) seconds"
    fi

fi

finish_up

