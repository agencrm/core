<?php

// app/Http/Controllers/Web/FormController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Inertia\Inertia;

class FormController extends Controller
{
    /**
     * Show the list of forms.
     */
    public function index()
    {
        $forms = Form::latest()->paginate(15);

        return Inertia::render('Forms/FormIndex', [
            'forms' => $forms,
        ]);
    }

    /**
     * Show a single form by id.
     */
    public function show(int $id)
    {
        $form = Form::findOrFail($id);

        return Inertia::render('Forms/ShowForm', [
            'form' => $form,
        ]);
    }
}
