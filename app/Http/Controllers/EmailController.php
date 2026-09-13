<?php
/** @noinspection PhpUnused */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;

class EmailController extends Controller
{

    public function showEmails()
    {

        return view('emails', [
            'templates' => Template::all(),
        ]);

    }

    public function updateEmails(Request $request)
    {
        // Pick up all the submitted input.
        $input = $request->all();

        // Loop through and look for specific formatting of field names, then update the associated template.
        foreach ($input as $key => $value) {
            if (stristr($key, '__subject')) {
                $template_name = str_replace('__subject', '', $key);
                $template = Template::where('name', $template_name)->first();
                $template->subject = $value ?? '';
                $template->save();
            }
            if (stristr($key, '__body')) {
                $template_name = str_replace('__body', '', $key);
                $template = Template::where('name', $template_name)->first();
                $template->body = $value ?? '';
                $template->save();
            }
        }

        return redirect('/emails');

    }

}
