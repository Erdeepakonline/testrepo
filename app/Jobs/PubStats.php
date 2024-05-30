<?php 
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Campaign;
use App\Models\User;
use App\Models\AdImpression;
use App\Models\CountriesIps;
use App\Models\UserCampClickLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendNotificationMail;
use Illuminate\Support\Facades\Artisan;
class PubStats implements ShouldQueue{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $response;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($response)
    {
        //Artisan::call('queue:listen');
        $this->response = $response;
    }
    public function __destruct()
    {
        //Artisan::call('queue:work --stop-when-empty');
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    { 
        $impData = $this->response();
        $ad_rate = DB::table('pub_rate_masters')->select('*')->where('status', 0)->where('category_id', $impData['website_category'])->where('country_name', $impData['country'])->first();
         if (empty($ad_rate)) {
            $ad_rate = DB::table('categories')->where('status', 1)->where('trash', 0)->select('*')->where('id', $impData['website_category'])->first();
        }
        if($impData['pricing_model']== 'CPM'){
            $adv_cpm = $impData['cpm'];
        }else{
            $adv_cpm = $ad_rate->cpm;
        }
        $cpm = ($ad_rate->cpm * $ad_rate->pub_cpm) / 100;
        DB::table('users')->where('uid', $impData['advertiser_code'])->decrement('wallet', $adv_cpm);
        DB::table('users')->where('uid', $impData['publisher_code'])->increment('pub_wallet', $cpm);
        // DB::table('pub_adunits')->where('web_code', $impData['website_id'])->where('uid', $impData['publisher_code'])->where('ad_code', $impData['adunit_id'])->increment('impressions', 1);
        $sessid = 'SESID' . strtoupper(uniqid());
        $ucountry = strtoupper($impData['country']);
        $adimp = new AdImpression();
        $device_os = strtolower($impData['device_os']);
        $adimp->impression_id = 'IMP' . strtoupper(uniqid());
        $adimp->ad_session_id = $sessid;
        $adimp->campaign_id = $impData['campaign_id'];
        $adimp->advertiser_code = $impData['advertiser_code'];
        $adimp->publisher_code = $impData['publisher_code'];
        $adimp->adunit_id = $impData['adunit_id'];
        $adimp->website_id = $impData['website_id'];
        $adimp->device_type = $impData['device_type'];
        $adimp->device_os = $device_os;
        $adimp->ip_addr = $impData['ip_addr'];
        $adimp->country = $ucountry;
        $adimp->ad_type = $impData['ad_type'];
        $adimp->amount = $adv_cpm;
        $adimp->website_category = $impData['website_category'];
        $adimp->pub_imp_credit = $cpm;
        $adimp->uni_imp_id = md5($impData['advertiser_code'] . $impData['campaign_id'] . $impData['device_os'] . $impData['device_type'] . $ucountry . date('Ymd'));
        $adimp->uni_bd_id = md5($impData['advertiser_code'] . $impData['campaign_id'] . date('Ymd'));
        $adimp->save();
    }  
  
}