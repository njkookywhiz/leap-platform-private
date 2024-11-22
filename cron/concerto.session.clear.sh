#!/bin/bash

(
  flock -n 9 || exit 0

  . /root/env.sh
  /usr/bin/php /app/leap/bin/console leap:sessions:clear --env=prod
) 9>/data/lock/leap.session.clear.lock
