<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Exception;

class ZoomMeetingService
{
    protected $client;
    protected $client_id;
    protected $client_secret;
    protected $account_id;

    public function __construct()
    {
        $this->client_id = config('zoom.client_id');
        $this->client_secret = config('zoom.client_secret');
        $this->account_id = config('zoom.account_id');
        
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Obtener token de acceso de Zoom
     */
    public function getAccessToken()
    {
        try {
            $auth_string = base64_encode($this->client_id . ':' . $this->client_secret);
            
            $response = $this->client->request('POST', 'https://zoom.us/oauth/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth_string,
                    'Host' => 'zoom.us',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->account_id,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Zoom Access Token obtenido exitosamente', [
                'expires_in' => $data['expires_in'],
                'token_type' => $data['token_type']
            ]);

            return $data['access_token'];

        } catch (ClientException $e) {
            $response_body = $e->getResponse()->getBody()->getContents();
            Log::error('Error obteniendo token de Zoom', [
                'status_code' => $e->getResponse()->getStatusCode(),
                'response' => $response_body
            ]);
            
            throw new Exception('No se pudo obtener el token de acceso: ' . $response_body);
        }
    }

    /**
     * Registrar un usuario en un meeting de Zoom
     */
    public function registerUserInMeeting($meetingId, $userEmail, $fullName, $lastName = null)
    {
        try {
            $accessToken = $this->getAccessToken();
            
            // Split inteligente del nombre completo
            $nameParts = $this->splitFullName($fullName);
            
            $registrationData = [
                'email' => $userEmail,
                'first_name' => $nameParts['first_name'],
                'last_name' => $lastName ?: $nameParts['last_name'], // Usar lastName pasado o el calculado
            ];

            Log::info('Registrando usuario en Zoom', [
                'meeting_id' => $meetingId,
                'email' => $userEmail,
                'full_name_original' => $fullName,
                'first_name_processed' => $nameParts['first_name'],
                'last_name_processed' => $nameParts['last_name'],
                'registration_data' => $registrationData
            ]);

            $response = $this->client->request('POST', "https://api.zoom.us/v2/meetings/{$meetingId}/registrants", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $registrationData
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Usuario registrado exitosamente en meeting', [
                'meeting_id' => $meetingId,
                'user_email' => $userEmail,
                'registrant_id' => $data['registrant_id'] ?? null
            ]);

            return [
                'success' => true,
                'join_url' => $data['join_url'],
                'registrant_id' => $data['registrant_id'],
                'data' => $data
            ];

        } catch (ClientException $e) {
            $response_body = $e->getResponse()->getBody()->getContents();
            $status_code = $e->getResponse()->getStatusCode();
            
            Log::error('Error registrando usuario en meeting', [
                'meeting_id' => $meetingId,
                'user_email' => $userEmail,
                'status_code' => $status_code,
                'response' => $response_body
            ]);

            // Si el usuario ya está registrado, intentamos obtener su información
            if ($status_code === 409) {
                return [
                    'success' => false,
                    'already_registered' => true,
                    'message' => 'El usuario ya está registrado en este meeting'
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $response_body
            ];

        } catch (Exception $e) {
            Log::error('Error general en registro de meeting', [
                'meeting_id' => $meetingId,
                'user_email' => $userEmail,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener información de un meeting
     */
    public function getMeetingInfo($meetingId)
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $response = $this->client->request('GET', "https://api.zoom.us/v2/meetings/{$meetingId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (ClientException $e) {
            Log::error('Error obteniendo info de meeting', [
                'meeting_id' => $meetingId,
                'response' => $e->getResponse()->getBody()->getContents()
            ]);
            
            return null;
        }
    }

    /**
     * Obtener registrants de un meeting
     */
    public function getMeetingRegistrants($meetingId)
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $response = $this->client->request('GET', "https://api.zoom.us/v2/meetings/{$meetingId}/registrants", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (ClientException $e) {
            Log::error('Error obteniendo registrants', [
                'meeting_id' => $meetingId,
                'response' => $e->getResponse()->getBody()->getContents()
            ]);
            
            return null;
        }
    }

    /**
     * Split inteligente del nombre completo según las reglas especificadas
     */
    private function splitFullName($fullName)
    {
        // Limpiar espacios extra y dividir por espacios
        $words = array_filter(explode(' ', trim($fullName)), function($word) {
            return !empty(trim($word));
        });
        $words = array_values($words); // Reindexar array
        $wordCount = count($words);

        if ($wordCount <= 1) {
            // Solo una palabra o vacío
            return [
                'first_name' => $words[0] ?? 'Usuario',
                'last_name' => 'Sin Apellido'
            ];
        } elseif ($wordCount == 2) {
            // 2 palabras: primera = nombre, segunda = apellido
            return [
                'first_name' => $words[0],
                'last_name' => $words[1]
            ];
        } elseif ($wordCount == 3) {
            // 3 palabras: primera = nombre, otras dos = apellidos
            return [
                'first_name' => $words[0],
                'last_name' => $words[1] . ' ' . $words[2]
            ];
        } elseif ($wordCount == 4) {
            // 4 palabras: dos primeras = nombres, dos segundas = apellidos
            return [
                'first_name' => $words[0] . ' ' . $words[1],
                'last_name' => $words[2] . ' ' . $words[3]
            ];
        } else {
            // Más de 4 palabras: dos primeras = nombres, el resto = apellidos
            $firstNames = array_slice($words, 0, 2);
            $lastNames = array_slice($words, 2);
            
            return [
                'first_name' => implode(' ', $firstNames),
                'last_name' => implode(' ', $lastNames)
            ];
        }
    }
}