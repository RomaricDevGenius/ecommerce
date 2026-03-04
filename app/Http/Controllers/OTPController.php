<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OtpConfiguration;

class OTPController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:otp_configurations'])->only('configure_index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function configure_index()
    {
        // S'assurer qu'une configuration Ligdicash existe toujours
        OtpConfiguration::firstOrCreate(
            ['type' => 'ligdicash'],
            ['value' => 0]
        );

        // Récupérer les configurations en plaçant Ligdicash en premier
        $otp_configurations = OtpConfiguration::orderByRaw(
            "CASE WHEN type = 'ligdicash' THEN 0 ELSE 1 END"
        )->get();
        return view('backend.otp_systems.configurations.index', compact('otp_configurations'));
    }

    public function loginConfigure(){
        return view('backend.otp_systems.configurations.login_configuration');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateActivationSettings(Request $request)
    {
        $otp_configuration = OtpConfiguration::where('type', $request->type)->first();
        if($otp_configuration!=null){
            $otp_configuration->value = $request->value;
            $otp_configuration->save();
        }
        else{
            $otp_configuration = new OtpConfiguration;
            $otp_configuration->type = $request->type;
            $otp_configuration->value = $request->value;
            $otp_configuration->save();
        }
        if($request->value == 1){
            OtpConfiguration::where('id','!=', $otp_configuration->id)
                        ->where('value', 1)
                        ->update(['value' => 0 ]);
        }
        return '1';
    }

    /**
     * Update the specified resource in .env
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_credentials(Request $request)
    {
        $types = $request->input('types');
        if (!is_array($types) || empty($types)) {
            flash(translate('Invalid request. Please try again.'))->error();
            return back();
        }

        try {
            $path = base_path('.env');
            if (!file_exists($path)) {
                flash(translate('.env file not found.'))->error();
                return back();
            }
            if (!is_writable($path)) {
                flash(translate('The .env file is not writable. Please check file permissions on the server.'))->error();
                return back();
            }

            $contents = file_get_contents($path);
            foreach ($types as $type) {
                $type = trim((string) $type);
                if ($type === '') {
                    continue;
                }
                $val = $request->get($type, '');
                $contents = $this->overWriteEnvFileContents($contents, $type, $val);
            }

            if (file_put_contents($path, $contents) === false) {
                flash(translate('Unable to save .env file. Please check permissions.'))->error();
                return back();
            }

            flash(translate('Settings updated successfully'))->success();
        } catch (\Throwable $e) {
            flash(translate('Something went wrong: ') . $e->getMessage())->error();
        }

        return back();
    }

    /**
     * Update or append a key in .env file contents (returns modified string).
     */
    protected function overWriteEnvFileContents($contents, $type, $val)
    {
        $val = '"' . str_replace('"', '\\"', trim($val)) . '"';
        $newLine = $type . '=' . $val;

        // Match line that starts with TYPE= (with optional quotes)
        $pattern = '/^' . preg_quote($type, '/') . '=.*$/m';
        if (preg_match($pattern, $contents)) {
            return preg_replace($pattern, $newLine, $contents);
        }

        return rtrim($contents) . "\n" . $newLine . "\n";
    }
}
