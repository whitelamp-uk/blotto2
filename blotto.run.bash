

get_args () {
    cfg=""
    verbose=""
    while [ 1 ]
    do
        if [[ "$1" == "-"* ]]
        then
            if    [[ "$1" == *"v"* ]]
            then
                verbose="1"
            fi
            shift
        fi
        if [ ! "$@" ]
        then
            return
        fi
        cfg="$1"
        shift
    done
}



# Set up
echo "----------------"
date '+%Y-%m-%d %H:%M:%S'
wd="$(pwd)"
cd "$(dirname $0)"
start=$SECONDS


# Definitions
get_args "$@"
if [ ! "$cfg" ]
then
    echo "/bin/bash $0 [-options] path_to_config_file"
    echo "  options can be combined eg. -nv"
    echo "    -n = no tidying"
    echo "    -v = verbose (echo full log to STDOUT)"
    exit 101
fi
/usr/bin/php  "$cfg"
if [ $? != 0 ]
then
    echo "Config file \"$cfg\" is not usable"
    exit 102
fi
now="$(date '+%Y_%m_%d_%H_%M_%S')"
pdir="$(pwd)/scripts"
echo -n "blotto running on: "
/usr/bin/php  "$pdir/define.php" "$cfg" BLOTTO_MC_NAME
if [ $? != 0 ]
then
    exit 103
fi
ldys="$( /usr/bin/php  "$pdir/define.php" "$cfg" BLOTTO_LOG_DURN_DAYS  )"
ldir="$( /usr/bin/php  "$pdir/define.php" "$cfg" BLOTTO_LOG_DIR        )"
lfil="blotto.run.$now.log"
lsql="blotto.run.$now.sql"

# Report log location
echo "Writing to log file: $ldir/$lfil"


# Tidy
find "$ldir" -mtime +$ldys -type f -delete
rm -f "$ldir/"*.sql.last.log


# Execute
if [ "$verbose" ]
then
    /bin/bash "$pdir/blotto.bash" "$@" 2>&1 | tee "$ldir/$lfil"
    err="$?"
else
    /bin/bash "$pdir/blotto.bash" "$@" 2>&1 > "$ldir/$lfil"
    err="$?"
fi


# Log latest SQL
mkdir "$ldir/$lsql"
found=$(ls -l "$ldir/"*.last*.log | wc -l)
if [ $found -gt 0 ]
then
    cp "$ldir/"*.last*.log "$ldir/$lsql/"
fi

# Return manual terminal to working directory
cd "$wd"


# Report script run time
echo "Script run time = $(($SECONDS-$start)) seconds"


# Report log location again
echo "Lots more detail in log file: $ldir/$lfil"


# Draw a line
echo "----------------"


# Exit with main script exit status
exit $err

