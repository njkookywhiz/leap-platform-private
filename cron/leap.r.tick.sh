#!/bin/bash

. /root/env.sh
/usr/bin/php /app/leap/bin/console leap:test:run _leap-tick --env=prod