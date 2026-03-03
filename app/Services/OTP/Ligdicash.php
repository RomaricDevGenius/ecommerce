<?php

namespace App\Services\OTP;

use App\Contracts\SendSms;

class Ligdicash implements SendSms
{
    /**
     * Envoi d'un SMS via l'API Ligdicash.
     *
     * @param string $to          Numéro de téléphone au format international (ex: 226XXXXXXXXX)
     * @param string $from        Non utilisé par Ligdicash mais gardé pour compatibilité
     * @param string $text        Contenu du message
     * @param mixed  $template_id Identifiant de template éventuel (non utilisé ici)
     *
     * @return array|null         Réponse décodée de l'API ou null en cas d'erreur silencieuse
     */
    public function send($to, $from, $text, $template_id)
    {
        $apiKey = env('LIGDICASH_API_KEY');
        $token  = env('LIGDICASH_TOKEN');

        if (empty($apiKey) || empty($token) || empty($to) || empty($text)) {
            return null;
        }

        $query = http_build_query([
            'api_key' => $apiKey,
            'token'   => $token,
            'to'      => $to,
            'text'    => $text,
        ]);

        $url = 'https://sms.ligdicash.io/api/send_sms.php?' . $query;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
        ]);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            return null;
        }

        $decoded = json_decode($response, true);

        return $decoded;
    }
}

