<?php

// app/Http/Controllers/Web/WebhookController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Inertia\Inertia;

class WebhookController extends Controller
{
    /**
     * Show the list of webhook hits
     */
    public function hits()
    {
        //$flows = Webhook::latest()->paginate(15);

        return Inertia::render('settings/Webhooks/WebhookHitsIndex', [
            //'flows' => $flows,
        ]);
    }


}
