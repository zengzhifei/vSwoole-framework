#!/bin/bash
# start the Swoole Server
# example: swoole.sh Http

# Server start log file
log=''
# cmd dir
cmd=''
# swoole dir
swoole=''
# server
server=$1
# script dir
dir=$(cd `dirname $0`; pwd)
# now time
time=$(date "+%Y-%m-%d %H:%M:%S")

# define
if [ ! "${log}" ]; then
    log="${dir}/swoole.log"
fi
if [ ! "${cmd}" ]; then
    cmd=$(whereis php)
fi
if [ ! "${swoole}" ]; then
    swoole="${dir}/../../public/swoole.php"
fi

# check
if [ ! -e "${log}" ]; then
    touch ${log}
fi
if [ ! -w "${log}" ]; then
    chmod 755 ${log}
fi
if [ ! "${server}" ]; then
    echo "${time} Arguments Server is Empty Or Invalid" >> ${log}
    exit 1
fi
if [ ! "${cmd}" ]; then
    echo "${time} Server Cmd: PHP is Not Found" >> ${log}
    exit 1
else
    cmd="/${cmd#*/}"
fi
if [ ! -e "${swoole}" ]; then
    echo "${time} Server Script: swoole is Not Found" >> ${log}
    exit 1
fi

# function
isStart(){
    status=$(ps -ef | grep "${1}" | grep -v grep | wc -l)
    return ${status}
}
startServer(){
    isStart "$1"
    if [ 0 -eq $? ]; then
        startReturn=$(${cmd} ${swoole} start ${server})
        sleep 10
        isStart "$1"
        if [ 0 -eq $? ]; then
            echo "${time} Server: ${server} Start Failed" >> ${log}
            echo ${startReturn} >> ${log}
            exit 1
        else
            echo "${time} Server: ${server} Start Success" >> ${log}
            exit 0
        fi
    fi
}

# Server
startServer "Swoole_${server}_Server master"
