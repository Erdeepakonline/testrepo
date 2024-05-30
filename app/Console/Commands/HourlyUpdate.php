<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IpStack;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class HourlyUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hour:ipStack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hourly insert ip from redis';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $redisKey = date('YmdH',time() - 3600);
        $redis_data = json_decode(Redis::get($redisKey), true);
        if(!is_null($redis_data)){
            $data = [];
            foreach($redis_data as $key => $value){
                $data[] = ['ip_addrs' =>   $value['ip'], 'continent_code' => $value['continent_code'], 'continent_name' => $value['continent_name'], 'country_code' => $value['country_code'], 'country_name' =>  $value['country_name'], 'region_code' => $value['region_code'],  'region_name' => $value['region_name'], 'city' => $value['city'],  'zip' => $value['zip'], 'time_zone' => $value['time_zone']['id'], 'created_at' =>  date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            } 
            $dataArr =  array_chunk($data,500);
            foreach($dataArr as $value) {
                IpStack::insert($value);
            }  
            Redis::del($redisKey);
        }
        $this->info('Hourly ip stack has been send successfully');
   }
}
