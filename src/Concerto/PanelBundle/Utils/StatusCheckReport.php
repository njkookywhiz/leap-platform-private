<?php

namespace Leap\PanelBundle\Utils;

interface StatusCheckReport {

    public function getErrorsString();

    public function isOk();
}
