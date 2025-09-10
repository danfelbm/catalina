<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro en Meetings Zoom - {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-center mb-2 text-gray-800">
                🎥 Sistema de Registro en Meetings Zoom
            </h1>
            <p class="text-center text-gray-600 mb-8">Registra usuarios automáticamente y obtén links personalizados</p>

            <!-- Información del usuario actual -->
            @auth
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <div class="bg-blue-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-blue-800">Usuario actual: {{ Auth::user()->name }}</p>
                        <p class="text-blue-600 text-sm">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-yellow-800">
                    <strong>⚠️ No estás logueado.</strong> 
                    <a href="{{ route('login') }}" class="text-yellow-600 underline">Inicia sesión</a> para probar el registro automático.
                </p>
            </div>
            @endauth

            <!-- Alertas -->
            @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-red-400">❌</span>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if (session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-green-400">✅</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if (session('warning'))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-yellow-400">⚠️</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">{{ session('warning') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Registro Automático (Usuario Logueado) -->
                @auth
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-green-600">🚀 Registro Automático</h2>
                    <p class="text-gray-600 mb-6">Ingresa el ID de un meeting y serás registrado automáticamente con tu cuenta actual.</p>
                    
                    <form action="{{ route('zoom.join-event') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="auto_meeting_id" class="block text-sm font-medium text-gray-700 mb-2">ID del Meeting de Zoom</label>
                            <input type="text" 
                                   id="auto_meeting_id" 
                                   name="meeting_id"
                                   placeholder="84453386755" 
                                   value="84453386755"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                   required>
                            <p class="text-xs text-gray-500 mt-1">Meeting ID real preconfigurado para pruebas</p>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-green-500 text-white py-3 px-6 rounded-lg hover:bg-green-600 transition duration-200 font-semibold">
                            🎯 Unirse al Evento Ahora
                        </button>
                    </form>
                    
                    <div class="mt-4 p-3 bg-green-50 rounded-lg">
                        <p class="text-xs text-green-700">
                            <strong>¿Cómo funciona?</strong><br>
                            1. Te registra automáticamente en el meeting<br>
                            2. Te redirige directo a tu link personal<br>
                            3. ¡Entras al meeting sin pasos adicionales!
                        </p>
                    </div>
                </div>
                @endauth

                <!-- Registro Manual -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-blue-600">📝 Registro Manual</h2>
                    <p class="text-gray-600 mb-6">Registra cualquier usuario especificando sus datos manualmente.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="manual_meeting_id" class="block text-sm font-medium text-gray-700 mb-2">ID del Meeting</label>
                            <input type="text" 
                                   id="manual_meeting_id" 
                                   placeholder="ej: 123456789" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email del Usuario</label>
                            <input type="email" 
                                   id="email" 
                                   placeholder="usuario@ejemplo.com" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                <input type="text" 
                                       id="first_name" 
                                       placeholder="Juan" 
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Apellido</label>
                                <input type="text" 
                                       id="last_name" 
                                       placeholder="Pérez" 
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <button onclick="registerUser()" 
                                class="w-full bg-blue-500 text-white py-3 px-6 rounded-lg hover:bg-blue-600 transition duration-200 font-semibold">
                            👤 Registrar Usuario
                        </button>
                    </div>
                </div>

                <!-- Información de Meeting -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-purple-600">ℹ️ Información del Meeting</h2>
                    <p class="text-gray-600 mb-6">Obtén información detallada de cualquier meeting.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="info_meeting_id" class="block text-sm font-medium text-gray-700 mb-2">ID del Meeting</label>
                            <input type="text" 
                                   id="info_meeting_id" 
                                   placeholder="ej: 123456789" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <button onclick="getMeetingInfo()" 
                                class="w-full bg-purple-500 text-white py-3 px-6 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold">
                            🔍 Obtener Información
                        </button>
                    </div>
                </div>

                <!-- Lista de Registrants -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-indigo-600">👥 Lista de Registrants</h2>
                    <p class="text-gray-600 mb-6">Ve quién está registrado en un meeting específico.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="registrants_meeting_id" class="block text-sm font-medium text-gray-700 mb-2">ID del Meeting</label>
                            <input type="text" 
                                   id="registrants_meeting_id" 
                                   placeholder="ej: 123456789" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <button onclick="getMeetingRegistrants()" 
                                class="w-full bg-indigo-500 text-white py-3 px-6 rounded-lg hover:bg-indigo-600 transition duration-200 font-semibold">
                            📋 Ver Registrants
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resultados -->
            <div id="results" class="mt-8" style="display: none;">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-semibold mb-4 text-gray-800">📋 Resultado:</h3>
                    <pre id="result-content" class="bg-gray-100 p-4 rounded-lg text-sm overflow-auto max-h-96 whitespace-pre-wrap"></pre>
                </div>
            </div>

            <!-- Links de Demo -->
            <div class="mt-8 bg-gray-100 rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">🎮 Links de Demo Rápido</h3>
                <p class="text-gray-600 mb-4">Para probar rápidamente (requiere login):</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <a href="{{ route('zoom.demo-join', '84453386755') }}" 
                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-center transition duration-200 font-semibold">
                        🎯 Meeting Real: 84453386755
                    </a>
                    <a href="{{ route('zoom.demo-join', '987654321') }}" 
                       class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-center transition duration-200">
                        Demo Meeting 987654321
                    </a>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Nota: Meeting 84453386755 es real. Los otros son IDs de ejemplo.
                </p>
            </div>

            <!-- Loading -->
            <div id="loading" class="fixed inset-0 bg-gray-500 bg-opacity-50 flex items-center justify-center" style="display: none;">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mr-3"></div>
                        <span>Procesando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configurar CSRF token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function showLoading() {
            $('#loading').show();
        }

        function hideLoading() {
            $('#loading').hide();
        }

        function showResult(data) {
            $('#result-content').text(JSON.stringify(data, null, 2));
            $('#results').show();
            $('#results')[0].scrollIntoView({ behavior: 'smooth' });
        }

        function registerUser() {
            const meetingId = $('#manual_meeting_id').val();
            const email = $('#email').val();
            const firstName = $('#first_name').val();
            const lastName = $('#last_name').val();

            if (!meetingId || !email || !firstName) {
                alert('Meeting ID, Email y Nombre son obligatorios');
                return;
            }

            showLoading();

            $.post('/zoom-meeting/register-user', {
                meeting_id: meetingId,
                email: email,
                first_name: firstName,
                last_name: lastName
            })
            .done(function(data) {
                showResult(data);
                if (data.success) {
                    // Limpiar formulario
                    $('#manual_meeting_id, #email, #first_name, #last_name').val('');
                }
            })
            .fail(function(xhr) {
                showResult({error: 'Error en la solicitud', details: xhr.responseJSON});
            })
            .always(function() {
                hideLoading();
            });
        }

        function getMeetingInfo() {
            const meetingId = $('#info_meeting_id').val();
            
            if (!meetingId) {
                alert('ID del Meeting es obligatorio');
                return;
            }

            showLoading();

            $.post('/zoom-meeting/get-meeting-info', {
                meeting_id: meetingId
            })
            .done(function(data) {
                showResult(data);
            })
            .fail(function(xhr) {
                showResult({error: 'Error en la solicitud', details: xhr.responseJSON});
            })
            .always(function() {
                hideLoading();
            });
        }

        function getMeetingRegistrants() {
            const meetingId = $('#registrants_meeting_id').val();
            
            if (!meetingId) {
                alert('ID del Meeting es obligatorio');
                return;
            }

            showLoading();

            $.post('/zoom-meeting/get-registrants', {
                meeting_id: meetingId
            })
            .done(function(data) {
                showResult(data);
            })
            .fail(function(xhr) {
                showResult({error: 'Error en la solicitud', details: xhr.responseJSON});
            })
            .always(function() {
                hideLoading();
            });
        }
    </script>
</body>
</html>