<?php

namespace Keikogi\NewsDelivery\Core;

interface SourceInterface
{
    public function url();

    public function type();

    public function prepare(&$flowItem, &$typeItem);

    public function run();

    public function save();
}
