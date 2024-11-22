#!/bin/bash

(
  flock -n 9 || exit 0

  . /root/env.sh
  PROCESS="forker.R";

  if ! ps --ppid 1 -F | grep -v grep | grep $PROCESS > /dev/null; then
    echo "`date`: forker process not running";
    /usr/bin/php /app/leap/bin/console leap:forker:start --env=prod;
  fi
) 9>/var/lock/leap.forker.guard.lock