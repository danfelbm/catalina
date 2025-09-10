<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jubaer\Zoom\Facades\Zoom;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZoomTestController extends Controller
{
    /**
     * Página principal de pruebas de Zoom
     */
    public function index()
    {
        return view('zoom-test.index');
    }

    /**
     * Crear reunión de prueba
     */
    public function createMeeting(Request $request)
    {
        try {
            $meetingData = [
                "topic" => $request->input('topic', 'Reunión de Prueba Laravel'),
                "agenda" => $request->input('agenda', 'Agenda de prueba para testing'),
                "type" => 2, // Scheduled meeting
                "duration" => (int) $request->input('duration', 60),
                "timezone" => $request->input('timezone', 'America/Bogota'),
                "password" => $request->input('password', '123456'),
                "start_time" => $request->input('start_time', Carbon::now()->addHour()->format('Y-m-d\TH:i:s')),
                "settings" => [
                    'join_before_host' => $request->boolean('join_before_host', false),
                    'host_video' => $request->boolean('host_video', true),
                    'participant_video' => $request->boolean('participant_video', false),
                    'mute_upon_entry' => $request->boolean('mute_upon_entry', true),
                    'waiting_room' => $request->boolean('waiting_room', false),
                    'audio' => $request->input('audio', 'both'),
                    'auto_recording' => $request->input('auto_recording', 'none'),
                    'approval_type' => (int) $request->input('approval_type', 0),
                ],
            ];

            $result = Zoom::createMeeting($meetingData);
            
            Log::info('Zoom Create Meeting Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Create Meeting Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de una reunión
     */
    public function getMeeting(Request $request)
    {
        try {
            $meetingId = $request->input('meeting_id');
            
            if (!$meetingId) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID de reunión es requerido'
                ], 400);
            }

            $result = Zoom::getMeeting($meetingId);
            
            Log::info('Zoom Get Meeting Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Get Meeting Error', [
                'error' => $e->getMessage(),
                'meeting_id' => $request->input('meeting_id')
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las reuniones
     */
    public function getAllMeetings()
    {
        try {
            $result = Zoom::getAllMeeting();
            
            Log::info('Zoom Get All Meetings Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Get All Meetings Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reuniones próximas
     */
    public function getUpcomingMeetings()
    {
        try {
            $result = Zoom::getUpcomingMeeting();
            
            Log::info('Zoom Get Upcoming Meetings Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Get Upcoming Meetings Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reuniones pasadas
     */
    public function getPastMeetings()
    {
        try {
            $result = Zoom::getPreviousMeetings();
            
            Log::info('Zoom Get Past Meetings Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Get Past Meetings Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar reunión
     */
    public function updateMeeting(Request $request)
    {
        try {
            $meetingId = $request->input('meeting_id');
            
            if (!$meetingId) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID de reunión es requerido'
                ], 400);
            }

            $meetingData = [
                "topic" => $request->input('topic', 'Reunión Actualizada'),
                "agenda" => $request->input('agenda', 'Agenda actualizada'),
                "duration" => (int) $request->input('duration', 90),
                "timezone" => $request->input('timezone', 'America/Bogota'),
                "password" => $request->input('password', '654321'),
            ];

            $result = Zoom::updateMeeting($meetingId, $meetingData);
            
            Log::info('Zoom Update Meeting Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Update Meeting Error', [
                'error' => $e->getMessage(),
                'meeting_id' => $request->input('meeting_id')
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar reunión
     */
    public function deleteMeeting(Request $request)
    {
        try {
            $meetingId = $request->input('meeting_id');
            
            if (!$meetingId) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID de reunión es requerido'
                ], 400);
            }

            $result = Zoom::deleteMeeting($meetingId);
            
            Log::info('Zoom Delete Meeting Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Delete Meeting Error', [
                'error' => $e->getMessage(),
                'meeting_id' => $request->input('meeting_id')
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuarios de Zoom
     */
    public function getUsers(Request $request)
    {
        try {
            $params = [
                'status' => $request->input('status', 'active'),
                'page_size' => $request->input('page_size', 10),
                'page_number' => $request->input('page_number', 1),
            ];

            $result = Zoom::getUsers($params);
            
            Log::info('Zoom Get Users Result', $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Zoom Get Users Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}