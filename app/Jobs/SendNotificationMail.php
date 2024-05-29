<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class SendNotificationMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $response;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($response)
    {
        $this->response = $response;
    }
    public function __destruct()
    {
        // Artisan::call('queue:work --stop-when-empty');
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    { 
        $notification = new Notification();
        $notification->notif_id = gennotificationuniq();
        $notification->title = 'Wallet Balance is low !';
        $notification->noti_desc = 'Dear Advertiser Your wallet balance is low. Please add fund into your wallet.';
        $notification->noti_type = 1;
        $notification->noti_for = 1;
        $notification->all_users = 0;
        $notification->status = 1;
        if ($notification->save()) {
            $noti = new UserNotification();
            $noti->notifuser_id = gennotificationuseruniq();
            $noti->noti_id = $notification->id;
            $noti->user_id = $this->response['user_id'];
            $noti->user_type = 1;
            $noti->view = 0;
            $noti->created_at = Carbon::now();
            $noti->updated_at = now();
            $noti->save();
            /* Send mail to User Wallet is low */
            $data['details'] = [
                'subject' => 'Dear Advertiser Wallet balance is low please add fund into your wallet  - 7Search PPC ',
                'fullname' => $this->response['name'],
                'usersid' => $this->response['user_id']
            ];
            $subject = 'Dear Advertiser Wallet balance is low please add fund into your wallet - 7Search PPC';
            $data["email"] = $this->response['email'];
            $data["title"] = $subject;
            $body = View('emailtemp.userwalletlow', $data);
            /* User Mail Section */
            sendmailUser($subject, $body, $this->response['email']);
        }
    }
}