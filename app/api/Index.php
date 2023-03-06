<?php

namespace app\api\controller;

class Index
{
    private $userid = '1901575702315264';
    private $cookie = 'c2Lm35rhbYdR5rzLWIm3jR8wHgkWj57FCS19uFBpEm1m11jvV9A8EXGwJHDO3Bq11Z89HW0d';
    //private $cookie = '14Djhn7IMd0AySxWLReh3uavAeE8cTYTGDoQsBxbbPxciBvpgRx9ngItqRHCuQoP0jc6';

    public function test(){
        $do = new \iqiyi\iqiyi($this->cookie,$this->userid);
        $res = $do->vipDailyTask();
        return (json($res));
    }
}