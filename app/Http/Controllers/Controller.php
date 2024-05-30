<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="7Searchppc API Documentation",
 *     version="2.0",
 *     description="7Searchppc API version-2",
 *     @OA\Contact(
 *         email="admin@admin.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Servers({
 *     @OA\Server(
 *         url="https://services.7searchppc.in",
 *         description="Development server"
 *     ),
 *     @OA\Server(
 *         url="http://127.0.0.1:8000",
 *         description="Local server"
 *     )
 * })
 * 
 * @OA\Tag(
 *     name="Ad Reports",
 *     description="Operations related to ad reports for **Admin**.",
 * )
 * @OA\Tag(
 *     name="Ad Unit",
 *     description="Operations related to ad Unit for **Publisher**.",
 * )
 * @OA\Tag(
 *     name="Categories",
 *     description="Operations related to categories for **Advertiser and Publisher**.",
 * )
 * @OA\Tag(
 *     name="Countries",
 *     description="Operations related to countries.",
 * )
 * @OA\Tag(
 *     name="Coupon",
 *     description="Operations related to coupon.",
 * )
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Operations related to dashboard for **Advertiser and Publisher**.",
 * )
 * @OA\Tag(
 *     name="Feedback",
 *     description="Operations related to feedback for **Advertiser and Publisher**.",
 * )
 * @OA\Tag(
 *     name="Messengers",
 *     description="Operations related to messengers.",
 * )
 * @OA\Tag(
 *     name="Notification",
 *     description="Operations related to notification for **Advertiser and Publisher**.",
 * )
 * @OA\Tag(
 *     name="Payout",
 *     description="Operations related to payouts **Admin**.",
 * )
 * @OA\Tag(
 *     name="Payouts & Wallet",
 *     description="Operations related to payouts & wallet.",
 * )
 * @OA\Tag(
 *     name="Profile",
 *     description="Operations related to profile for **Advertiser and Publisher**.",
 * )
    * @OA\Tag(
    *     name="Advertiser - Support",
    *     description="Operations related to support for **Advertiser and Publisher**.",
    * )
 * @OA\Tag(
 *     name="Publisher - Support",
 *     description="Operations related to support for **Advertiser and Publisher**.",
 * )
 * @OA\Tag(
 *     name="Transactions",
 *     description="Operations related to transactions.",
 * )
 * @OA\Tag(
 *     name="User",
 *     description="Operations related to users for **Advertiser and Publisher**.",
 * )
 * @OA\Tag(
 *     name="User Reports",
 *     description="Operations related to user reports for **Admin**.",
 * )
 * @OA\Tag(
 *     name="Websites",
 *     description="Operations related to websites for **Publisher**.",
 * )
 * @OA\Tag(
 *     name="App Categories",
 *     description="Operations related to appilcation.",
 * )
 *  * @OA\Tag(
 *     name="App Feedback",
 *     description="Operations related to appilcation.",
 * )
 * @OA\Tag(
 *     name="App Notifications",
 *     description="Operations related to appilcation.",
 * )
 * @OA\Tag(
 *     name="Manage Admin Agents",
 *     description="Operations related to **Agents Admin**.",
 * )
 * @OA\Tag(
 *     name="Assign Agents Adveriser & Publisher",
 *     description="Operations related to **Advertiser & Publisher**.",
 * )
 *   @OA\Tag(
 *     name="Manage Payment Gateway",
 *     description="Operations related to **Advertiser & Admin**.",
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
