<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas Zoom Laravel - {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">
                🎥 Pruebas de Zoom Laravel Package
            </h1>

            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                <p><strong>⚠️ Configuración requerida:</strong></p>
                <p>Asegúrate de configurar las variables ZOOM_CLIENT_KEY, ZOOM_CLIENT_SECRET y ZOOM_ACCOUNT_ID en tu archivo .env</p>
            </div>

            <!-- Resultados -->
            <div id="results" class="mb-8" style="display: none;">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4">📋 Resultado de la prueba:</h3>
                    <pre id="result-content" class="bg-gray-100 p-4 rounded text-sm overflow-auto max-h-96"></pre>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Crear Reunión -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-green-600">➕ Crear Reunión</h2>
                    <div class="space-y-3">
                        <input type="text" id="topic" placeholder="Título de la reunión" 
                               class="w-full p-2 border rounded" value="Reunión de Prueba Laravel">
                        <textarea id="agenda" placeholder="Agenda" 
                                class="w-full p-2 border rounded" rows="3">Agenda de prueba para testing del paquete Zoom Laravel</textarea>
                        <input type="number" id="duration" placeholder="Duración (minutos)" 
                               class="w-full p-2 border rounded" value="60">
                        <input type="datetime-local" id="start_time" 
                               class="w-full p-2 border rounded">
                        <input type="text" id="password" placeholder="Contraseña" 
                               class="w-full p-2 border rounded" value="123456">
                        <button onclick="createMeeting()" 
                                class="w-full bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600">
                            Crear Reunión
                        </button>
                    </div>
                </div>

                <!-- Obtener Reunión -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-blue-600">🔍 Obtener Reunión</h2>
                    <div class="space-y-3">
                        <input type="text" id="get_meeting_id" placeholder="ID de la reunión" 
                               class="w-full p-2 border rounded">
                        <button onclick="getMeeting()" 
                                class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                            Obtener Reunión
                        </button>
                    </div>
                </div>

                <!-- Actualizar Reunión -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-yellow-600">✏️ Actualizar Reunión</h2>
                    <div class="space-y-3">
                        <input type="text" id="update_meeting_id" placeholder="ID de la reunión" 
                               class="w-full p-2 border rounded">
                        <input type="text" id="update_topic" placeholder="Nuevo título" 
                               class="w-full p-2 border rounded" value="Reunión Actualizada">
                        <input type="number" id="update_duration" placeholder="Nueva duración" 
                               class="w-full p-2 border rounded" value="90">
                        <button onclick="updateMeeting()" 
                                class="w-full bg-yellow-500 text-white py-2 px-4 rounded hover:bg-yellow-600">
                            Actualizar Reunión
                        </button>
                    </div>
                </div>

                <!-- Eliminar Reunión -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-red-600">🗑️ Eliminar Reunión</h2>
                    <div class="space-y-3">
                        <input type="text" id="delete_meeting_id" placeholder="ID de la reunión" 
                               class="w-full p-2 border rounded">
                        <button onclick="deleteMeeting()" 
                                class="w-full bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600">
                            Eliminar Reunión
                        </button>
                    </div>
                </div>

                <!-- Listar Reuniones -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-purple-600">📋 Listar Reuniones</h2>
                    <div class="space-y-3">
                        <button onclick="getAllMeetings()" 
                                class="w-full bg-purple-500 text-white py-2 px-4 rounded hover:bg-purple-600">
                            Todas las Reuniones
                        </button>
                        <button onclick="getUpcomingMeetings()" 
                                class="w-full bg-purple-400 text-white py-2 px-4 rounded hover:bg-purple-500">
                            Reuniones Próximas
                        </button>
                        <button onclick="getPastMeetings()" 
                                class="w-full bg-purple-300 text-white py-2 px-4 rounded hover:bg-purple-400">
                            Reuniones Pasadas
                        </button>
                    </div>
                </div>

                <!-- Usuarios -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-indigo-600">👥 Obtener Usuarios</h2>
                    <div class="space-y-3">
                        <select id="user_status" class="w-full p-2 border rounded">
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                            <option value="pending">Pendientes</option>
                        </select>
                        <input type="number" id="page_size" placeholder="Tamaño de página" 
                               class="w-full p-2 border rounded" value="10">
                        <button onclick="getUsers()" 
                                class="w-full bg-indigo-500 text-white py-2 px-4 rounded hover:bg-indigo-600">
                            Obtener Usuarios
                        </button>
                    </div>
                </div>

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
        // Configurar CSRF token para Ajax
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Set default start time to 1 hour from now
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            now.setHours(now.getHours() + 1);
            const offset = now.getTimezoneOffset();
            const adjustedTime = new Date(now.getTime() - (offset * 60000));
            document.getElementById('start_time').value = adjustedTime.toISOString().slice(0, 16);
        });

        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function showResult(data) {
            document.getElementById('result-content').textContent = JSON.stringify(data, null, 2);
            document.getElementById('results').style.display = 'block';
            document.getElementById('results').scrollIntoView({ behavior: 'smooth' });
        }

        function createMeeting() {
            showLoading();
            $.post('/zoom-test/create-meeting', {
                topic: $('#topic').val(),
                agenda: $('#agenda').val(),
                duration: $('#duration').val(),
                start_time: $('#start_time').val(),
                password: $('#password').val(),
                host_video: true,
                participant_video: false,
                mute_upon_entry: true
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

        function getMeeting() {
            const meetingId = $('#get_meeting_id').val();
            if (!meetingId) {
                alert('Por favor ingresa el ID de la reunión');
                return;
            }
            
            showLoading();
            $.post('/zoom-test/get-meeting', {
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

        function getAllMeetings() {
            showLoading();
            $.get('/zoom-test/get-all-meetings')
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

        function getUpcomingMeetings() {
            showLoading();
            $.get('/zoom-test/get-upcoming-meetings')
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

        function getPastMeetings() {
            showLoading();
            $.get('/zoom-test/get-past-meetings')
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

        function updateMeeting() {
            const meetingId = $('#update_meeting_id').val();
            if (!meetingId) {
                alert('Por favor ingresa el ID de la reunión');
                return;
            }
            
            showLoading();
            $.post('/zoom-test/update-meeting', {
                meeting_id: meetingId,
                topic: $('#update_topic').val(),
                duration: $('#update_duration').val(),
                agenda: 'Agenda actualizada desde la prueba'
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

        function deleteMeeting() {
            const meetingId = $('#delete_meeting_id').val();
            if (!meetingId) {
                alert('Por favor ingresa el ID de la reunión');
                return;
            }
            
            if (!confirm('¿Estás seguro de que quieres eliminar esta reunión?')) {
                return;
            }
            
            showLoading();
            $.post('/zoom-test/delete-meeting', {
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

        function getUsers() {
            showLoading();
            $.post('/zoom-test/get-users', {
                status: $('#user_status').val(),
                page_size: $('#page_size').val(),
                page_number: 1
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