#!/bin/bash

. /root/env.sh
/usr/bin/php /app/leap/bin/console leap:schedule:tick --env=prod