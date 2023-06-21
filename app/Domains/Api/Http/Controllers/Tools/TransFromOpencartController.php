<?php

namespace App\Domains\Api\Http\Controllers\Tools;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransFromOpencartController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getForm()
    {
        return view('admin.tools.trans_from_opencart');
    }


    public function update(Request $request)
    {
        if(!empty($request->post('transText'))){
            $transText = str_replace("\r", '', $request->post('transText'));
            $transText = explode("\n", $transText);

            $new[] = "return [";
            foreach ($transText as $value) {
                $value = trim($value);
                if(strstr($value, '//')){
                    $new[] = $value;
                }else if(strstr($value, '$_')){
                    // https://www.phpliveregex.com/
                    preg_match('"\\$_\\[\\\'(.*)\\\'\\]\\s+\\=\\s*\\\'(.*)\\\'"', $value, $matches);
                    $new_str = "'".$matches[1]."'";
                    $length = strlen($new_str);
                    $c = 40 - $length;
                    $spaces = '';
                    for ($i=0; $i < $c; $i++) {
                        $spaces .= ' ';
                    }
                    $new[] = $new_str . $spaces . "=> '".$matches[2]."',";

                    $toArray[] = ['key'=> $matches[1], 'value' => $matches[2]];
                }
            }
            $transText = implode("\n", $new) . "\n];";
            $data['transText'] = $transText;

            $data['toArray'] = $toArray ?? [];
        }

        return view('admin.tools.trans_from_opencart', $data);
    }
}
