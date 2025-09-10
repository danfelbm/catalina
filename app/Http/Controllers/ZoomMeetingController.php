<?php

namespace App\Http\Controllers;

use App\Services\ZoomMeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ZoomMeetingController extends Controller
{
    protected $zoomService;

    public function __construct(ZoomMeetingService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * Página principal para registro en meetings
     */
    public function index()
    {
        return view('zoom-meeting.index');
    }

    /**
     * Unirse automáticamente a un evento - Redirige directamente
     */
    public function joinEvent(Request $request)
    {
        $user = Auth::user();
        
        // ID del meeting (puedes configurarlo dinámicamente)
        $meetingId = $request->input('meeting_id');
        
        if (!$meetingId) {
            return back()->withErrors('ID de reunión es requerido.');
        }

        try {
            // Registrar usuario automáticamente (el servicio hará el split del nombre)
            $result = $this->zoomService->registerUserInMeeting(
                $meetingId,
                $user->email,
                $user->name // El servicio procesará este nombre completo automáticamente
            );

            if ($result['success']) {
                // Redirigir directamente al meeting con su link personal
                return redirect()->away($result['join_url']);
            }

            // Si ya está registrado, podrías intentar obtener su link
            if (isset($result['already_registered']) && $result['already_registered']) {
                return back()->with('warning', 'Ya estás registrado en este evento. El link fue enviado a tu email.');
            }

            return back()->withErrors($result['message']);

        } catch (\Exception $e) {
            Log::error('Error en joinEvent', [
                'user_id' => $user->id,
                'meeting_id' => $meetingId,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors('No se pudo conectar con Zoom. Intente más tarde.');
        }
    }

    /**
     * Registrar usuario manualmente (con confirmación)
     */
    public function registerUser(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|string',
            'email' => 'required|email',
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
        ]);

        try {
            $result = $this->zoomService->registerUserInMeeting(
                $request->meeting_id,
                $request->email,
                $request->first_name,
                $request->last_name
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de un meeting
     */
    public function getMeetingInfo(Request $request)
    {
        $meetingId = $request->input('meeting_id');
        
        if (!$meetingId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de reunión es requerido'
            ], 400);
        }

        try {
            $meetingInfo = $this->zoomService->getMeetingInfo($meetingId);
            
            if ($meetingInfo) {
                return response()->json([
                    'success' => true,
                    'meeting' => $meetingInfo
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Meeting no encontrado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener registrants de un meeting
     */
    public function getMeetingRegistrants(Request $request)
    {
        $meetingId = $request->input('meeting_id');
        
        if (!$meetingId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de reunión es requerido'
            ], 400);
        }

        try {
            $registrants = $this->zoomService->getMeetingRegistrants($meetingId);
            
            if ($registrants) {
                return response()->json([
                    'success' => true,
                    'registrants' => $registrants
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los registrants'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Demostración para cualquier usuario logueado
     */
    public function demoJoin($meetingId)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('message', 'Debes iniciar sesión para unirte al evento.');
        }

        $user = Auth::user();

        try {
            $result = $this->zoomService->registerUserInMeeting(
                $meetingId,
                $user->email,
                $user->name // El servicio procesará este nombre completo automáticamente
            );

            if ($result['success']) {
                // Redirigir directamente al meeting
                return redirect()->away($result['join_url']);
            }

            return redirect()->back()->withErrors('No se pudo registrar en el evento: ' . $result['message']);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error de conexión con Zoom.');
        }
    }
}