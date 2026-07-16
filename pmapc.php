<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Plan de Manejo Ambiental, Productivo y Comercial (PMAPC) | Somos Sumapaz</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <script>
        const timestamp = new Date().getTime();
        document.write(`<link rel="stylesheet" href="assets/styles.css?v=${timestamp}">`);
    </script>

    <style>
        /* Premium Stepper & Form Styles */
        .stepper-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            position: relative;
            background: #fff;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow-x: auto;
        }

        .stepper-line {
            position: absolute;
            top: 50%;
            left: 5%;
            right: 5%;
            height: 3px;
            background: #E0E0E0;
            z-index: 1;
            transform: translateY(-50%);
        }

        .stepper-line-progress {
            position: absolute;
            top: 50%;
            left: 5%;
            width: 0%;
            height: 3px;
            background: var(--primary-green);
            z-index: 2;
            transform: translateY(-50%);
            transition: width 0.4s ease;
        }

        .step-node {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            min-width: 70px;
        }

        .step-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #FFF;
            border: 3px solid #E0E0E0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .step-node.active .step-circle {
            border-color: var(--primary-green);
            background: var(--primary-green);
            color: #FFF;
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
        }

        .step-node.completed .step-circle {
            border-color: var(--primary-green);
            background: #FFF;
            color: var(--primary-green);
        }

        .step-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 0.5rem;
            text-align: center;
            white-space: nowrap;
        }

        .step-node.active .step-label {
            color: var(--primary-green);
            font-weight: 700;
        }

        .form-section-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2.5rem;
            margin-bottom: 2rem;
            display: none; /* Controlled by JS */
            animation: fadeIn 0.4s ease;
        }

        .form-section-card.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-header {
            border-bottom: 2px solid #F0F2F5;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            color: var(--primary-green);
            font-size: 1.5rem;
            margin-bottom: 0.4rem;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .format-block {
            background: #FAF9F6;
            border: 1px solid #EAE6DF;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .format-title {
            color: var(--earth-brown);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px dashed #E0DCD3;
            padding-bottom: 0.5rem;
        }

        .format-title span {
            background: var(--earth-brown);
            color: #FFF;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: var(--font-family);
        }

        /* Form Controls Table Styles */
        .table-input-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 1rem;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
        }

        .table-input {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .table-input th {
            background: #F3F4F6;
            color: #374151;
            padding: 0.8rem;
            font-weight: 600;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }

        .table-input td {
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid #E5E7EB;
            vertical-align: middle;
        }

        .table-input input, 
        .table-input select, 
        .table-input textarea {
            width: 100%;
            padding: 0.5rem 0.7rem;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            background: #FFF;
            font-size: 0.85rem;
        }

        .table-input input:focus, 
        .table-input select:focus, 
        .table-input textarea:focus {
            border-color: var(--primary-green);
            outline: none;
        }

        .row-action-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #EF4444;
            padding: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .row-action-btn:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .add-row-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #E8F5E9;
            color: var(--primary-green);
            border: 1px solid var(--light-green);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: all 0.2s;
        }

        .add-row-btn:hover {
            background: var(--light-green);
            color: #FFF;
        }

        .prefilled-card {
            background-color: #EFEBE4;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary-green);
        }

        .prefilled-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-label {
            font-size: 0.75rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 0.95rem;
            color: #333;
            font-weight: 500;
        }

        .producer-selector-card {
            background: #FFF;
            border: 1px solid #EAE6DF;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .producer-selector-card label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--earth-brown);
        }

        .producer-select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            background-color: #FAFAFA;
        }

        .nav-buttons-container {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            border-top: 2px solid #F0F2F5;
            padding-top: 1.5rem;
        }

        .btn-nav {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-prev {
            background: #EAEAEF;
            color: #555566;
        }

        .btn-prev:hover {
            background: #D5D5DF;
        }

        .btn-next {
            background: var(--primary-green);
            color: #FFF;
            margin-left: auto;
        }

        .btn-next:hover {
            background: #1B5E20;
        }

        /* Responsive adjustments & Desktop-like Mobile spacing */
        @media (max-width: 768px) {
            .container {
                max-width: 100% !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            .form-section-card {
                padding: 1rem 0.75rem !important;
                margin-bottom: 1.25rem !important;
            }
            .stepper-container {
                padding: 0.75rem !important;
                margin-bottom: 1.5rem !important;
            }
            .step-label {
                display: none; /* Hide labels on mobile to fit dots */
            }
            .form-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
                gap: 0.75rem !important;
            }
            .table-input-container {
                overflow-x: auto !important;
                margin-bottom: 1rem !important;
                -webkit-overflow-scrolling: touch;
            }
            .table-input {
                min-width: 850px !important;
                width: 100% !important;
            }
            h1 {
                font-size: 1.6rem !important;
            }
            p {
                font-size: 0.95rem !important;
            }
            .format-title {
                font-size: 1.05rem !important;
                padding: 0.6rem 0.8rem !important;
            }
        }
        /* IA processing styles */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .ia-spinner {
            border: 3px solid #0D47A1;
            border-top: 3px solid transparent;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
    </style>
</head>

<body>
    <!-- Sync Banner -->
    <div id="sync-banner" style="display: none; background: #FFF3E0; color: #E65100; padding: 12px 20px; text-align: center; font-weight: 600; font-size: 0.95rem; border-bottom: 2px solid #FFE0B2; box-shadow: 0 2px 4px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; font-family: 'Inter', sans-serif;">
        <span id="sync-banner-text">Tienes registros guardados sin conexión pendientes de sincronizar.</span>
        <button onclick="syncPendingRecords()" style="margin-left: 15px; background: #E65100; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 700; transition: background 0.2s;">Sincronizar ahora</button>
    </div>

    <!-- Offline Notification Toast -->
    <div id="offline-toast" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #323232; color: #fff; padding: 12px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; font-family: 'Inter', sans-serif; font-size: 0.9rem; align-items: center; gap: 10px;">
        <svg style="width: 20px; height: 20px; fill: #FFA726;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <span id="offline-toast-text">Modo sin conexión activo</span>
    </div>

    <!-- Static Header -->
    <header id="main-header">
        <nav class="navbar-custom">
            <div class="container" style="display: flex; align-items: center;">
                <a href="index.html" onclick="handleLogoClick(event)">
                    <img src="assets/logo_somossumapaz.png" alt="Somos Sumapaz" class="logo-img">
                </a>
                <div class="search-container" style="margin-left: auto; display: flex; align-items: center; gap: 1rem;">
                    <button onclick="openBackupModal()" class="nav-link-btn" id="btn-backup-header"
                        style="margin: 0; background-color: #8D6E63; color: #FFF; border: none; font-weight: 600; padding: 8px 14px; border-radius: 6px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <svg style="width: 18px; height: 18px; fill: #FFF;" viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
                        Respaldo
                    </button>
                    <!-- Admin Options (Login / Dashboard / Inscripción) -->
                    <div id="header-auth-container" style="display: none; align-items: center; gap: 1rem;">
                        <a href="inscripcion_productores.html" class="nav-link-btn" id="btn-register"
                            style="margin: 0; background-color: transparent; color: #FBF4DE; border: none; font-weight: 600; padding: 8px 14px; border-radius: 6px;">
                            Registrar Productor
                        </a>
                        <a href="productores_registrados.html" class="nav-link-btn" id="btn-dashboard"
                            style="margin: 0; background-color: transparent; color: #FBF4DE; border: none; font-weight: 600; padding: 8px 14px; border-radius: 6px;">
                            Dashboard
                        </a>
                        <a href="pmapc.html" class="nav-link-btn" id="btn-pmapc"
                            style="margin: 0; background-color: rgba(255,255,255,0.2); color: #FBF4DE; border: 1px solid #FBF4DE; font-weight: 600; padding: 8px 14px; border-radius: 6px;">
                            PMAPC
                        </a>
                        <button onclick="logout()" class="nav-link-btn" id="btn-logout"
                            style="margin: 0; background-color: transparent; cursor: pointer; color: #FBF4DE; border: none; padding: 8px; border-radius: 6px;"
                            title="Cerrar Sesión">
                            <svg fill="none" viewBox="0 0 24 24" stroke="#FBF4DE" stroke-width="2" style="width: 24px; height: 24px; transform: scaleX(-1);">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 class="serif" style="font-size: 2.2rem; color: var(--earth-brown); display: inline-block; position: relative; padding-bottom: 5px; margin-bottom: 0.5rem;">
                Plan de Manejo Ambiental, Productivo y Comercial (PMAPC)
                <span style="position: absolute; bottom: 0; left: 20%; width: 60%; height: 3px; background-color: var(--light-green); border-radius: 2px;"></span>
            </h1>
            <p style="color: #666; font-size: 1.1rem; margin-top: 0.5rem;">Planificación integral y sostenible para unidades productivas rurales de Sumapaz.</p>
        </div>

        <!-- Producer Selector standalone -->
        <div id="producer-selector-box" class="producer-selector-card" style="position: relative;">
            <label for="select-producer-input">Selecciona el Productor / Unidad Productiva *</label>
            <input type="text" id="select-producer-input" placeholder="Cargando productores..." class="producer-select" autocomplete="off" style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; background-color: #FAFAFA; box-sizing: border-box;">
            <input type="hidden" id="select-producer" name="select-producer">
            <div id="select-producer-results" style="display: none; position: absolute; left: 1.5rem; right: 1.5rem; background: white; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 220px; overflow-y: auto; z-index: 1000; margin-top: 4px; box-sizing: border-box;"></div>
        </div>

        <!-- Interview Transcript Upload (IA) -->
        <div id="transcript-upload-box" class="producer-selector-card" style="display: none; border-left: 5px solid #2196F3; margin-top: 1.5rem; margin-bottom: 1.5rem;">
            <label style="font-weight: 600; color: #1E88E5; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                <svg style="width: 24px; height: 24px; fill: #1E88E5;" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                Autocompletar PMAPC con IA (Transcripción de Entrevista)
            </label>
            <p style="font-size: 0.85rem; color: #666; margin-top: 0.25rem; margin-bottom: 1rem;">
                Sube un archivo de texto (.txt) con la transcripción de la entrevista realizada al productor. La IA analizará el texto y rellenará automáticamente todos los campos correspondientes del PMAPC.
            </p>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <input type="file" id="transcript-file-input" accept=".txt" style="display: none;">
                    <button type="button" onclick="document.getElementById('transcript-file-input').click()" class="btn" style="background-color: #2196F3; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 6px; font-family: 'Inter', sans-serif;">
                        <svg style="width: 18px; height: 18px; fill: white;" viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4v6zm-4 2h14v2H5v-2z"/></svg>
                        Seleccionar Archivo .txt
                    </button>
                    <span id="selected-file-name" style="font-size: 0.9rem; color: #555; font-style: italic;">Ningún archivo seleccionado</span>
                </div>
                
                <!-- Progress status / progress spinner -->
                <div id="ia-processing-status" style="display: none; align-items: center; gap: 10px; background-color: #E3F2FD; color: #0D47A1; padding: 12px; border-radius: 6px; font-weight: 600; font-size: 0.9rem;">
                    <div class="ia-spinner"></div>
                    <span id="ia-processing-text">Procesando archivo con la IA... Esto puede tomar entre 15 y 30 segundos. Por favor no cierres la página.</span>
                </div>
                
                <!-- Action button -->
                <button type="button" id="btn-process-transcript" class="btn" disabled style="background-color: #4CAF50; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: not-allowed; font-weight: 600; width: fit-content; opacity: 0.5; font-family: 'Inter', sans-serif;">
                    Iniciar Autocompletado con IA
                </button>
            </div>
        </div>

        <!-- Global Form Progress Bar -->
        <div id="form-progress-card" style="background: #fff; padding: 1.25rem 1.5rem; border-radius: 8px; box-shadow: var(--shadow); margin-top: 1.5rem; margin-bottom: 1.5rem; border: 1px solid #E5E7EB; display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span style="font-weight: 600; color: var(--earth-brown); font-size: 0.95rem; font-family: 'Inter', sans-serif;">Progreso de Diligenciamiento del PMAPC</span>
                <span id="form-progress-text" style="font-weight: 700; color: var(--primary-green); font-size: 1.1rem; font-family: 'Inter', sans-serif;">0% completado</span>
            </div>
            <div style="width: 100%; height: 10px; background-color: #E0E0E0; border-radius: 5px; overflow: hidden;">
                <div id="form-progress-bar" style="width: 0%; height: 100%; background-color: var(--primary-green); transition: width 0.4s ease; border-radius: 5px;"></div>
            </div>
        </div>

        <!-- Pre-filled info card (locks when loaded from URL or selected) -->
        <div id="producer-info-box" class="prefilled-card" style="display: none; background-color: #F4F1EA; border: 1px solid #D1C7BD; border-left: 5px solid var(--primary-green);">
            <h3 style="color: var(--primary-green); margin-bottom: 0.5rem; font-size: 1.15rem; font-weight: 700;">Datos del Productor (Edición Rápida)</h3>
            <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Puede corregir los datos aquí si es necesario; se guardarán automáticamente en su ficha al guardar el PMAPC.</p>
            <div class="prefilled-grid">
                <div>
                    <div class="info-label">Nombre Completo</div>
                    <div class="info-value" id="lbl-nombre" style="padding-top: 8px; font-weight: 600;">-</div>
                </div>
                <div>
                    <div class="info-label">Número Documento</div>
                    <div class="info-value" id="lbl-documento" style="padding-top: 8px; font-weight: 600;">-</div>
                </div>
                <div>
                    <label class="info-label" for="edit-vereda">Vereda / Corregimiento</label>
                    <input type="text" id="edit-vereda" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; margin-top: 4px; font-size: 0.9rem; background-color: #fff;">
                </div>
                <div>
                    <label class="info-label" for="edit-predio">Nombre del Predio</label>
                    <input type="text" id="edit-predio" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; margin-top: 4px; font-size: 0.9rem; background-color: #fff;">
                </div>
                <div>
                    <label class="info-label" for="edit-fecha-nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="edit-fecha-nacimiento" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; margin-top: 4px; font-size: 0.9rem; background-color: #fff;">
                </div>
                <div>
                    <label class="info-label" for="edit-telefono">Teléfono</label>
                    <input type="text" id="edit-telefono" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; margin-top: 4px; font-size: 0.9rem; background-color: #fff;">
                </div>
                <div>
                    <label class="info-label" for="edit-correo">Correo Electrónico</label>
                    <input type="email" id="edit-correo" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 6px; margin-top: 4px; font-size: 0.9rem; background-color: #fff;">
                </div>
            </div>
            <button id="btn-change-producer" class="btn" style="margin-top: 1.5rem; padding: 0.4rem 1.2rem; font-size: 0.85rem; background-color: var(--earth-brown); color: white; border: none; border-radius: 6px; cursor: pointer;">Cambiar Productor</button>
        </div>

        <!-- Stepper -->
        <div class="stepper-container">
            <div class="stepper-line"></div>
            <div class="stepper-line-progress" id="stepper-progress"></div>
            <div class="step-node active" onclick="goToStep(1)">
                <div class="step-circle">1</div>
                <div class="step-label">Estratégico</div>
            </div>
            <div class="step-node" onclick="goToStep(2)">
                <div class="step-circle">2</div>
                <div class="step-label">Mercado</div>
            </div>
            <div class="step-node" onclick="goToStep(3)">
                <div class="step-circle">3</div>
                <div class="step-label">Producción</div>
            </div>
            <div class="step-node" onclick="goToStep(4)">
                <div class="step-circle">4</div>
                <div class="step-label">Límites Amb.</div>
            </div>
            <div class="step-node" onclick="goToStep(5)">
                <div class="step-circle">5</div>
                <div class="step-label">Comercial</div>
            </div>
            <div class="step-node" onclick="goToStep(6)">
                <div class="step-circle">6</div>
                <div class="step-label">Finanzas</div>
            </div>
            <div class="step-node" onclick="goToStep(7)">
                <div class="step-circle">7</div>
                <div class="step-label">Sostenibilidad</div>
            </div>
            <div class="step-node" onclick="goToStep(8)">
                <div class="step-circle">8</div>
                <div class="step-label">Riesgos</div>
            </div>
        </div>



        <!-- Main Form -->
        <form id="pmapc-form" class="custom-form">
            <input type="hidden" id="productor_id" name="productor_id">

            <!-- ================= STEP 1: ESTRATEGICO ================= -->
            <div class="form-section-card active" id="step-1">
                <div class="section-header">
                    <h2>Módulo 1: Componente Estratégico y Territorial</h2>
                    <p>Identificación de la unidad, direccionamiento estratégico, propuesta de valor y FODA.</p>
                </div>

                <!-- F01 Identidad -->
                <div class="format-block">
                    <div class="format-title"><span>F01</span> Identidad de la Unidad Productiva</div>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f01_nombre_organizacion">Nombre de la organización o unidad productiva</label>
                            <input type="text" id="f01_nombre_organizacion" name="f01_nombre_organizacion" placeholder="Ej. Asociación de Productores El Manantial">
                        </div>
                        <div class="form-group">
                            <label for="f01_tipo_actividad">Tipo de Actividad</label>
                            <select id="f01_tipo_actividad" name="f01_tipo_actividad">
                                <option value="">Seleccione...</option>
                                <option value="agricola">Agrícola</option>
                                <option value="pecuaria">Pecuaria</option>
                                <option value="artesanal">Artesanal</option>
                                <option value="agroindustrial">Agroindustrial</option>
                                <option value="servicios">Servicios</option>
                                <option value="otra">Otra</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="f01_ubicacion">Ubicación específica (Vereda, sector, predio)</label>
                            <input type="text" id="f01_ubicacion" name="f01_ubicacion" placeholder="Ej. Vereda San Juan, Predio La Laguna">
                        </div>
                        <div class="form-group">
                            <label for="f01_coordenadas">Coordenadas</label>
                            <input type="text" id="f01_coordenadas" name="f01_coordenadas" placeholder="Ej. 4.1234, -74.1234" required>
                        </div>
                        <div class="form-group">
                            <label for="f01_producto_principal">Producto o servicio principal</label>
                            <input type="text" id="f01_producto_principal" name="f01_producto_principal" placeholder="Ej. Queso Campesino o Papa Criolla">
                        </div>
                        <div class="form-group">
                            <label for="f01_estado_actual">Estado Actual</label>
                            <select id="f01_estado_actual" name="f01_estado_actual">
                                <option value="">Seleccione...</option>
                                <option value="idea">Idea de negocio</option>
                                <option value="produccion_inicial">Producción inicial</option>
                                <option value="negocio_marcha">Negocio en marcha</option>
                                <option value="asociacion">Asociación formalizada</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f01_descripcion_general">Descripción general del negocio</label>
                            <textarea id="f01_descripcion_general" name="f01_descripcion_general" rows="3" placeholder="Resumen breve sobre cómo opera la unidad productiva, sus clientes y su valor principal."></textarea>
                        </div>
                    </div>
                </div>

                <!-- F02 Direccionamiento -->
                <div class="format-block">
                    <div class="format-title"><span>F02</span> Direccionamiento Estratégico</div>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f02_mision">Misión (¿Para qué existe el negocio hoy?)</label>
                            <textarea id="f02_mision" name="f02_mision" rows="2" placeholder="Ej. Producir lácteos de excelente calidad cuidando los nacimientos de agua y pagando precios justos..."></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f02_vision">Visión (¿Cómo se ve en el futuro?)</label>
                            <textarea id="f02_vision" name="f02_vision" rows="2" placeholder="Ej. En 2028 ser el principal proveedor de queso orgánico en mercados campesinos de Bogotá..."></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f02_valores">Valores Institucionales (Mínimo tres, separados por coma)</label>
                            <input type="text" id="f02_valores" name="f02_valores" placeholder="Ej. Cuidado ambiental, Confianza, Cooperación, Transparencia">
                        </div>
                    </div>
                </div>

                <!-- F03 Propuesta de Valor -->
                <div class="format-block">
                    <div class="format-title"><span>F03</span> Propuesta de Valor</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="f03_problema">¿Por qué las personas compran su producto?</label>
                            <input type="text" id="f03_problema" name="f03_problema" placeholder="Ej. Por ser un producto orgánico, libre de químicos y de origen directo">
                        </div>
                        <div class="form-group">
                            <label for="f03_solucion">¿Qué beneficio recibe quien compra su producto? (Ejm. Es fresco, producido sin exceso de químicos, buena calidad, dura más, entre otros)</label>
                            <input type="text" id="f03_solucion" name="f03_solucion" placeholder="Ej. Producto más fresco, de mejor calidad y con mayor durabilidad">
                        </div>
                        <div class="form-group">
                            <label for="f03_diferencial">¿Qué lo hace diferente frente a otros productos o soluciones?</label>
                            <input type="text" id="f03_diferencial" name="f03_diferencial" placeholder="Ej. Cultivado con abono orgánico local y agua pura de nacimiento">
                        </div>
                        <div class="form-group">
                            <label for="f03_valor_ambiental">¿Qué valor ambiental aporta?</label>
                            <input type="text" id="f03_valor_ambiental" name="f03_valor_ambiental" placeholder="¿Cómo ayuda su forma de producir a cuidar el agua, el suelo o el ambiente?">
                        </div>
                        <div class="form-group">
                            <label for="f03_valor_social">¿Qué valor social o comunitario aporta?</label>
                            <input type="text" id="f03_valor_social" name="f03_valor_social" placeholder="¿Cómo beneficia su producción a su familia o a la comunidad?">
                        </div>
                        <div class="form-group">
                            <label for="f03_demostracion">¿Cómo se puede demostrar ese diferencial? (Ejm. Certificaciones si existen, buenas prácticas, fotografías, recomendaciones de clientes, producción limpia, visitas al predio, entre otros)</label>
                            <input type="text" id="f03_demostracion" name="f03_demostracion" placeholder="¿Cómo puede demostrar la calidad o el cuidado con que produce?">
                        </div>
                    </div>
                </div>

                <!-- F04 FODA -->
                <div class="format-block">
                    <div class="format-title"><span>F04</span> Análisis FODA Sistémico</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Fortalezas</label>
                            <textarea id="f04_fortalezas" name="f04_fortalezas" rows="3" placeholder="Puntos fuertes internos (ej. Saberes tradicionales, tierra propia, agua disponible)"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Oportunidades</label>
                            <textarea id="f04_oportunidades" name="f04_oportunidades" rows="3" placeholder="Factores externos favorables (ej. Aumento de demanda orgánica, ferias institucionales)"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Debilidades</label>
                            <textarea id="f04_debilidades" name="f04_debilidades" rows="3" placeholder="Puntos internos a mejorar (ej. Falta de transporte, empaques rudimentarios)"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Amenazas</label>
                            <textarea id="f04_amenazas" name="f04_amenazas" rows="3" placeholder="Riesgos externos (ej. Clima adverso, heladas, caída de precios por intermediarios)"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 2: MERCADO ================= -->
            <div class="form-section-card" id="step-2">
                <div class="section-header">
                    <h2>Módulo 2: Componente de Mercado y Cooperación</h2>
                    <p>Perfil de clientes, problemas de mercado, alianzas de cooperación territorial y validaciones comerciales.</p>
                </div>

                <!-- F05 Clientes -->
                <div class="format-block">
                    <div class="format-title"><span>F05</span> Perfil de Clientes, Consumidores y Compradores (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f05">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Tipo de Actor</th>
                                    <th>Perfil / características<br><small style="font-weight: normal; font-size: 0.78rem; color: #666; display: block; margin-top: 4px; line-height: 1.25;">¿Quién compra? ¿Qué busca cuando compra? ¿Qué cantidad compra?</small></th>
                                    <th>Ubicación</th>
                                    <th>Necesidad que atiende<br><small style="font-weight: normal; font-size: 0.78rem; color: #666; display: block; margin-top: 4px; line-height: 1.25;">¿Qué es lo que más valora ese comprador?</small></th>
                                    <th>Frecuencia de compra</th>
                                    <th>Criterio de compra</th>
                                    <th>Canal de contacto</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF05()">+ Añadir Actor</button>
                </div>

                <!-- F06 Problema de Mercado -->
                <div class="format-block">
                    <div class="format-title"><span>F06</span> Problema del Mercado</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="f06_necesidad">¿Qué buscan los compradores cuando adquieren su producto? (Ejm. calidad, frescura, precio, producción limpia, cercanía, producción local, entre otras).</label>
                            <input type="text" id="f06_necesidad" name="f06_necesidad" placeholder="Ej. Frescura, producción limpia y origen directo">
                        </div>
                        <div class="form-group">
                            <label for="f06_como_sabe">¿Cómo sabe que los compradores buscan eso?</label>
                            <input type="text" id="f06_como_sabe" name="f06_como_sabe" placeholder="Ej. Por comentarios directos de los clientes y porque lo preguntan en las ferias">
                        </div>
                        <div class="form-group">
                            <label for="f06_a_quien_afecta">¿Quiénes compran o podrían comprar este producto?</label>
                            <input type="text" id="f06_a_quien_afecta" name="f06_a_quien_afecta" placeholder="Ej. Familias del sector, restaurantes locales e intermediarios conscientes">
                        </div>
                        <div class="form-group">
                            <label for="f06_evidencia">¿Qué evidencia tiene? (Ejm. cliente frecuente, pedidos, participación en mercados, conversaciones, encargos, redes, entre otros).</label>
                            <input type="text" id="f06_evidencia" name="f06_evidencia" placeholder="Ej. Pedidos semanales fijos y aumento de clientes en redes sociales">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f06_oportunidad_organicos">¿Qué ventajas tiene su producto por ser producido en Sumapaz o mediante prácticas sostenibles?</label>
                            <textarea id="f06_oportunidad_organicos" name="f06_oportunidad_organicos" rows="2" placeholder="Ej. El valor del agua del páramo, la pureza de la zona y la producción limpia"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="f06_cambio">¿Ha cambiado lo que buscan sus compradores en los últimos años?</label>
                            <input type="text" id="f06_cambio" name="f06_cambio" placeholder="Ej. Sí, ahora se interesan más por la salud y buscan productos sin agroquímicos">
                        </div>
                        <div class="form-group">
                            <label for="f06_dificultad">¿Qué dificultades encuentra para vender su producto? (Ejm. precio, transporte, competencia, cantidad, calidad, empaque).</label>
                            <input type="text" id="f06_dificultad" name="f06_dificultad" placeholder="Ej. Alto costo del transporte y dificultad para conseguir empaques biodegradables">
                        </div>
                    </div>
                </div>

                <!-- F07 Cooperación -->
                <div class="format-block">
                    <div class="format-title"><span>F07</span> Cooperación Territorial y Alianzas Productivas (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f07">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Actor o aliado</th>
                                    <th style="width: 15%;">¿Qué aporta?</th>
                                    <th style="width: 15%;">¿Qué puede recibir?</th>
                                    <th style="width: 15%;">Posibilidad trabajo conjunto</th>
                                    <th style="width: 15%;">Aporte ambiental</th>
                                    <th style="width: 15%;">Acción concreta</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF07()">+ Añadir Aliado</button>
                </div>

                <!-- F08 Validacion -->
                <div class="format-block">
                    <div class="format-title"><span>F08</span> Validación de Mercado y Aceptación del Producto</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f08">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Método</th>
                                    <th>¿A quién se aplicó?</th>
                                    <th>Resultados Obtenidos</th>
                                    <th>Motivación de compra</th>
                                    <th>Soporte / Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Degustación / muestra</strong></td>
                                    <td><input type="text" name="f08_quien_degus" placeholder="Ej. Clientes de feria"></td>
                                    <td><input type="text" name="f08_resultado_degus" placeholder="Ej. 90% les gustó la textura"></td>
                                    <td><input type="text" name="f08_motivacion_degus" placeholder="Ej. Calidad del sabor y frescura"></td>
                                    <td><input type="text" name="f08_evidencia_degus" placeholder="Ej. Lista de firmas/fotos"></td>
                                </tr>
                                <tr>
                                    <td><strong>Venta previa</strong></td>
                                    <td><input type="text" name="f08_quien_ventas" placeholder="Ej. Vecinos y conocidos"></td>
                                    <td><input type="text" name="f08_resultado_ventas" placeholder="Ej. Compra regular de 20 bultos"></td>
                                    <td><input type="text" name="f08_motivacion_ventas" placeholder="Ej. Necesidad de abastecimiento local"></td>
                                    <td><input type="text" name="f08_evidencia_ventas" placeholder="Ej. Recibos de pago"></td>
                                </tr>
                                <tr>
                                    <td><strong>Carta de intención</strong></td>
                                    <td><input type="text" name="f08_quien_cartas" placeholder="Ej. Restaurante de Bogotá"></td>
                                    <td><input type="text" name="f08_resultado_cartas" placeholder="Ej. Interés de compra de 30 kg/semana"></td>
                                    <td><input type="text" name="f08_motivacion_cartas" placeholder="Ej. Búsqueda de productos limpios y origen de páramo"></td>
                                    <td><input type="text" name="f08_evidencia_cartas" placeholder="Ej. PDF de carta firmada"></td>
                                </tr>
                                <tr>
                                    <td><strong>Encuesta</strong></td>
                                    <td><input type="text" name="f08_quien_encuesta" placeholder="Ej. Clientes potenciales"></td>
                                    <td><input type="text" name="f08_resultado_encuesta" placeholder="Ej. 80% compraría el producto"></td>
                                    <td><input type="text" name="f08_motivacion_encuesta" placeholder="Ej. Relación precio-calidad y empaque ecológico"></td>
                                    <td><input type="text" name="f08_evidencia_encuesta" placeholder="Ej. Formularios físicos/digitales"></td>
                                </tr>
                                <tr>
                                    <td><strong>Entrevista</strong></td>
                                    <td><input type="text" name="f08_quien_entrevista" placeholder="Ej. Comprador de supermercado"></td>
                                    <td><input type="text" name="f08_resultado_entrevista" placeholder="Ej. Sugirió empaque de 500g"></td>
                                    <td><input type="text" name="f08_motivacion_entrevista" placeholder="Ej. Interés en productos con trazabilidad y valor social"></td>
                                    <td><input type="text" name="f08_evidencia_entrevista" placeholder="Ej. Audio o notas de reunión"></td>
                                </tr>
                                <tr>
                                    <td><strong>Participación en feria</strong></td>
                                    <td><input type="text" name="f08_quien_feria" placeholder="Ej. Público general en Bogotá"></td>
                                    <td><input type="text" name="f08_resultado_feria" placeholder="Ej. Todo el stock vendido"></td>
                                    <td><input type="text" name="f08_motivacion_feria" placeholder="Ej. Apoyo directo al campesinado y precio justo"></td>
                                    <td><input type="text" name="f08_evidencia_feria" placeholder="Ej. Fotos del stand"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="text" name="f08_metodo_otro" placeholder="Otro (Especifique)" style="font-weight: bold;">
                                    </td>
                                    <td><input type="text" name="f08_quien_otro" placeholder="Ej. A quién aplicó"></td>
                                    <td><input type="text" name="f08_resultado_otro" placeholder="Ej. Resultado"></td>
                                    <td><input type="text" name="f08_motivacion_otro" placeholder="Ej. Motivación"></td>
                                    <td><input type="text" name="f08_evidencia_otro" placeholder="Ej. Evidencia"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 3: PRODUCCION ================= -->
            <div class="form-section-card" id="step-3">
                <div class="section-header">
                    <h2>Módulo 3: Componente Productivo y Capacidad</h2>
                    <p>Ficha técnica de productos, flujo del proceso productivo, insumos requeridos y capacidad real.</p>
                </div>

                <!-- F09 Ficha Tecnica -->
                <div class="format-block">
                    <div class="format-title"><span>F09</span> Ficha Técnica del Producto o Servicio (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f09">
                            <thead>
                                <tr>
                                    <th>Producto / Servicio</th>
                                    <th>Descripción Técnica</th>
                                    <th>Unidad Medida</th>
                                    <th>Insumos Principales</th>
                                    <th>Almacenamiento/Uso</th>
                                    <th>Presentación / Empaque</th>
                                    <th>Final diferencial</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="dynamic-row-f09">
                                    <td><input type="text" name="f09_producto[]" placeholder="Ej. Queso Fresco"></td>
                                    <td><input type="text" name="f09_descripcion[]" placeholder="Queso madurado 3 días..."></td>
                                    <td><input type="text" name="f09_unidad[]" placeholder="Ej. kg, Libra"></td>
                                    <td><input type="text" name="f09_insumos[]" placeholder="Leche, cuajo, sal"></td>
                                    <td><input type="text" name="f09_almacenamiento[]" placeholder="Refrigerado 2-4°C"></td>
                                    <td><input type="text" name="f09_presentacion[]" placeholder="Bolsa vacío biodegradable"></td>
                                    <td><input type="text" name="f09_diferencial[]" placeholder="Ej. Cultivo orgánico de páramo"></td>
                                    <td>
                                        <button type="button" class="row-action-btn" onclick="removeRow(this)">
                                            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF09()">+ Añadir Fila</button>
                </div>

                <!-- F10 Proceso Productivo -->
                <div class="format-block">
                    <div class="format-title"><span>F10</span> Proceso Productivo (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f10">
                            <thead>
                                <tr>
                                    <th>Bien / Servicio</th>
                                    <th>Unidades a producir</th>
                                    <th>Actividad del Proceso</th>
                                    <th>Tiempo Estimado</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="dynamic-row-f10">
                                    <td><input type="text" name="f10_bien[]" placeholder="Ej. Queso Fresco"></td>
                                    <td><input type="number" name="f10_unidades[]" placeholder="Ej. 100"></td>
                                    <td><input type="text" name="f10_actividad[]" placeholder="Ej. Ordeño y filtrado de leche"></td>
                                    <td><input type="text" name="f10_tiempo[]" placeholder="Ej. 1 hora"></td>
                                    <td>
                                        <button type="button" class="row-action-btn" onclick="removeRow(this)">
                                            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF10()">+ Añadir Actividad</button>
                </div>

                <!-- F11 Insumos Necesarios -->
                <div class="format-block">
                    <div class="format-title"><span>F11</span> Insumos Necesarios (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f11">
                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th>Cantidad / unidad de medida</th>
                                    <th>Frecuencia</th>
                                    <th>Proveedor o fuente</th>
                                    <th>Toxicidad Relativa</th>
                                    <th>Impacto ambiental potencial</th>
                                    <th>Medida de manejo (Ambiental)</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="dynamic-row-f11">
                                    <td><input type="text" name="f11_insumo[]" placeholder="Ej. Fertilizante químico"></td>
                                    <td><input type="text" name="f11_cantidad[]" placeholder="Ej. 10 kg"></td>
                                    <td>
                                        <select name="f11_frecuencia[]">
                                            <option value="Mensual">Mensual</option>
                                            <option value="Trimestral">Trimestral</option>
                                            <option value="Ocasional">Ocasional</option>
                                            <option value="Permanente">Permanente</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f11_proveedor[]" placeholder="Ej. Distribuidora local"></td>
                                    <td>
                                        <select name="f11_toxicidad[]">
                                            <option value="Franja roja">Franja roja</option>
                                            <option value="Franja amarilla">Franja amarilla</option>
                                            <option value="Franja azul">Franja azul</option>
                                            <option value="Franja verde">Franja verde</option>
                                            <option value="N/A" selected>N/A</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f11_impacto[]" placeholder="Ej. Contaminación del suelo"></td>
                                    <td><input type="text" name="f11_manejo[]" placeholder="Ej. Dosificación estricta"></td>
                                    <td>
                                        <button type="button" class="row-action-btn" onclick="removeRow(this)">
                                            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF11()">+ Añadir Insumo</button>
                </div>

                <!-- F12 Capacidad Productiva -->
                <div class="format-block">
                    <div class="format-title"><span>F12</span> Capacidad de Producción</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="f12_produccion_estimada">Producción mensual real (Si es por cosecha o ciclo productivo, especifique).</label>
                            <input type="text" id="f12_produccion_estimada" name="f12_produccion_estimada" placeholder="¿Cuánto produce normalmente?">
                        </div>
                        <div class="form-group">
                            <label for="f12_produccion_maxima">¿Cuánto podría producir con los recursos que tiene actualmente?</label>
                            <input type="text" id="f12_produccion_maxima" name="f12_produccion_maxima" placeholder="Producción máxima posible">
                        </div>
                        <div class="form-group">
                            <label for="f12_area">Área destinada a la producción</label>
                            <input type="text" id="f12_area" name="f12_area" placeholder="m² o número de animales, según corresponda">
                        </div>
                        <div class="form-group">
                            <label for="f12_limitantes_prod">Limitantes productivos</label>
                            <input type="text" id="f12_limitantes_prod" name="f12_limitantes_prod" placeholder="Ej. Falta de termo-selladora, mano de obra">
                        </div>
                        <div class="form-group">
                            <label for="f12_limitantes_amb">Limitantes ambientales</label>
                            <input type="text" id="f12_limitantes_amb" name="f12_limitantes_amb" placeholder="Ej. Épocas de sequía, heladas">
                        </div>
                        <div class="form-group">
                            <label for="f12_capacidad_instalada">Capacidad Instalada</label>
                            <input type="text" id="f12_capacidad_instalada" name="f12_capacidad_instalada" placeholder="Ej. Capacidad máxima teórica de la planta">
                        </div>
                        <div class="form-group">
                            <label for="f12_capacidad_utilizada">Capacidad Utilizada</label>
                            <input type="text" id="f12_capacidad_utilizada" name="f12_capacidad_utilizada" placeholder="Ej. Uso actual real de la capacidad (e.g. 60%)">
                        </div>
                        <div class="form-group">
                            <label for="f12_misma_cantidad">¿Produce la misma cantidad durante todo el año? Si no, explique por qué.</label>
                            <input type="text" id="f12_misma_cantidad" name="f12_misma_cantidad" placeholder="Ej. No, en época de sequía disminuye un 40%">
                        </div>
                        <div class="form-group">
                            <label for="f12_alcanza_demanda">¿La producción actual alcanza para atender la demanda de sus compradores?</label>
                            <input type="text" id="f12_alcanza_demanda" name="f12_alcanza_demanda" placeholder="Ej. No, se pierden ventas por falta de cantidad constante">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f12_necesidad_sostenible">¿Qué necesita para aumentar la producción de manera sostenible?</label>
                            <textarea id="f12_necesidad_sostenible" name="f12_necesidad_sostenible" rows="2" placeholder="Ej. Adquirir maquinaria de empaque y establecer un sistema de riego silvopastoril"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 4: LIMITES AMBIENTALES ================= -->
            <div class="form-section-card" id="step-4">
                <div class="section-header">
                    <h2>Módulo 4: Límites Ambientales del Territorio</h2>
                    <p>Revisión sistemática obligatoria sobre las restricciones de recursos biológicos y físicos en el páramo.</p>
                </div>

                <!-- F12A Limites -->
                <div class="format-block">
                    <div class="format-title"><span>F12A</span> Límites Ambientales y Ajustes en el Plan de Producción</div>
                    
                    <!-- Box of frequent problems in the territory -->
                    <div class="info-alert-box" style="background-color: #F9FBE7; border-left: 5px solid #9E9D24; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-family: 'Inter', sans-serif;">
                        <h4 style="color: #558B2F; margin-top: 0; margin-bottom: 0.75rem; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                            <svg style="width: 20px; height: 20px; fill: #558B2F;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            Problemas frecuentes del territorio que deben considerarse antes de diligenciar la matriz:
                        </h4>
                        <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.88rem; line-height: 1.5; color: #3E2723; display: flex; flex-direction: column; gap: 6px;">
                            <li><strong>Heladas:</strong> pueden afectar la producción agrícola y pecuaria, ocasionando pérdidas en cultivos, afectaciones en pasturas y disminución de la disponibilidad de alimento para los animales.</li>
                            <li><strong>Sequías o disminución del agua:</strong> pueden impactar la disponibilidad hídrica para riego, consumo animal, transformación y demás actividades productivas.</li>
                            <li><strong>Erosión del suelo:</strong> se presenta con mayor frecuencia en zonas de pendiente, suelos descubiertos o con manejo inadecuado, reduciendo la fertilidad y aumentando la pérdida de suelo.</li>
                            <li><strong>Acceso al predio:</strong> las condiciones de ingreso al predio influyen en la comercialización, el transporte de productos, el ingreso de insumos y los costos logísticos.</li>
                        </ul>
                    </div>

                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f12a">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Recurso Ambiental</th>
                                    <th>Estado Actual</th>
                                    <th>Límite o Restricción</th>
                                    <th>Efecto en la Producción</th>
                                    <th>Acción Preventiva / Mitigación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Agua disponible</strong></td>
                                    <td><input type="text" name="f12a_estado_agua" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_agua" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_agua" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_agua" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Fuentes hídricas cercanas</strong></td>
                                    <td><input type="text" name="f12a_estado_fuentes" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_fuentes" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_fuentes" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_fuentes" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Suelo</strong></td>
                                    <td><input type="text" name="f12a_estado_suelo" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_suelo" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_suelo" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_suelo" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Pendiente / topografía</strong></td>
                                    <td><input type="text" name="f12a_estado_pendiente" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_pendiente" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_pendiente" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_pendiente" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Clima</strong></td>
                                    <td><input type="text" name="f12a_estado_clima" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_clima" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_clima" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_clima" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Biodiversidad</strong></td>
                                    <td><input type="text" name="f12a_estado_bio" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_bio" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_bio" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_bio" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso de insumos</strong></td>
                                    <td><input type="text" name="f12a_estado_insumos" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_insumos" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_insumos" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_insumos" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Manejo de residuos</strong></td>
                                    <td><input type="text" name="f12a_estado_residuos" placeholder="..."></td>
                                    <td><input type="text" name="f12a_limite_residuos" placeholder="..."></td>
                                    <td><input type="text" name="f12a_efecto_residuos" placeholder="..."></td>
                                    <td><input type="text" name="f12a_accion_residuos" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F12B Riesgos -->
                <div class="format-block">
                    <div class="format-title"><span>F12B</span> Matriz de Identificación de Riesgos y Peligros (SST)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f12b">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Tipo de riesgo</th>
                                    <th style="width: 15%;">Peligro o factor</th>
                                    <th style="width: 5%;">Sí</th>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 10%;">Frec. alta</th>
                                    <th style="width: 10%;">Frec. media</th>
                                    <th style="width: 10%;">Frec. baja</th>
                                    <th>Controles existentes</th>
                                    <th>Acción de mejora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Biológico -->
                                <tr>
                                    <td><strong>Biológico</strong></td><td>Virus</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_virus_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_virus_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_virus_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_virus_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_virus_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_virus_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_virus_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Biológico</strong></td><td>Bacterias</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_bacterias_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_bacterias_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_bacterias_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_bacterias_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_bacterias_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_bacterias_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_bacterias_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Biológico</strong></td><td>Picaduras</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_picaduras_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_picaduras_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_picaduras_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_picaduras_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_picaduras_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_picaduras_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_picaduras_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Biológico</strong></td><td>Mordeduras</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mordeduras_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mordeduras_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mordeduras_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mordeduras_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mordeduras_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_mordeduras_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_mordeduras_mejora" placeholder="Mejora"></td>
                                </tr>
                                <!-- Físico -->
                                <tr>
                                    <td><strong>Físico</strong></td><td>Temperatura</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_temperatura_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_temperatura_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_temperatura_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_temperatura_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_temperatura_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_temperatura_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_temperatura_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Físico</strong></td><td>Radiación solar</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_radiacion_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_radiacion_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_radiacion_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_radiacion_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_radiacion_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_radiacion_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_radiacion_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Físico</strong></td><td>Ruido</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_ruido_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_ruido_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_ruido_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_ruido_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_ruido_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_ruido_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_ruido_mejora" placeholder="Mejora"></td>
                                </tr>
                                <!-- Químico -->
                                <tr>
                                    <td><strong>Químico</strong></td><td>Polvos</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_polvos_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_polvos_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_polvos_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_polvos_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_polvos_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_polvos_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_polvos_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Químico</strong></td><td>Gases</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_gases_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_gases_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_gases_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_gases_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_gases_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_gases_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_gases_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Químico</strong></td><td>Material particulado</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_particulado_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_particulado_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_particulado_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_particulado_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_particulado_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_particulado_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_particulado_mejora" placeholder="Mejora"></td>
                                </tr>
                                <!-- Biomecánico -->
                                <tr>
                                    <td><strong>Biomecánico</strong></td><td>Posturas</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_posturas_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_posturas_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_posturas_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_posturas_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_posturas_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_posturas_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_posturas_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Biomecánico</strong></td><td>Movimientos repetitivos</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_movimientos_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_movimientos_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_movimientos_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_movimientos_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_movimientos_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_movimientos_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_movimientos_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Biomecánico</strong></td><td>Levantamiento de cargas</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_cargas_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_cargas_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_cargas_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_cargas_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_cargas_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_cargas_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_cargas_mejora" placeholder="Mejora"></td>
                                </tr>
                                <!-- Condiciones de seguridad -->
                                <tr>
                                    <td><strong>Cond. seguridad</strong></td><td>Mecánico - uso de herramientas</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mecanico_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mecanico_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mecanico_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mecanico_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_mecanico_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_mecanico_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_mecanico_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Cond. seguridad</strong></td><td>Locativo - superficies irregulares</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_locativo_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_locativo_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_locativo_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_locativo_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_locativo_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_locativo_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_locativo_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Cond. seguridad</strong></td><td>Eléctrico - baja tensión</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_electrico_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_electrico_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_electrico_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_electrico_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_electrico_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_electrico_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_electrico_mejora" placeholder="Mejora"></td>
                                </tr>
                                <tr>
                                    <td><strong>Cond. seguridad</strong></td><td>Accidentes de tránsito</td>
                                    <td class="text-center"><input type="checkbox" name="f12b_transito_si" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_transito_no" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_transito_f_alta" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_transito_f_media" value="1"></td>
                                    <td class="text-center"><input type="checkbox" name="f12b_transito_f_baja" value="1"></td>
                                    <td><input type="text" name="f12b_transito_controles" placeholder="Controles"></td>
                                    <td><input type="text" name="f12b_transito_mejora" placeholder="Mejora"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F12C Controles SST -->
                <div class="format-block">
                    <div class="format-title"><span>F12C</span> Controles Generales en SST</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f12c">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Acción preventiva o de control</th>
                                    <th style="width: 20%;">Riesgo que atiende</th>
                                    <th>Responsable</th>
                                    <th>Frecuencia</th>
                                    <th>Evidencia o soporte</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Uso de elementos de protección personal según la actividad: guantes, botas, tapabocas, gafas, sombrero o bloqueador solar.</td>
                                    <td>Biológico, físico, químico y mecánico</td>
                                    <td><input type="text" name="f12c_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f12c_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f12c_evidencia_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>Revisión del estado de herramientas, equipos, conexiones eléctricas y superficies de trabajo antes de iniciar la jornada.</td>
                                    <td>Mecánico, eléctrico y locativo</td>
                                    <td><input type="text" name="f12c_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f12c_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f12c_evidencia_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>Capacitación o explicación breve sobre manejo seguro de herramientas, levantamiento de cargas y posturas adecuadas.</td>
                                    <td>Biomecánico y condiciones de seguridad</td>
                                    <td><input type="text" name="f12c_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f12c_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f12c_evidencia_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>Organización del área de trabajo para evitar caídas, tropiezos, acumulación de residuos o materiales mal ubicados.</td>
                                    <td>Locativo y accidentes de trabajo</td>
                                    <td><input type="text" name="f12c_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f12c_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f12c_evidencia_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>Disponibilidad de botiquín básico, números de emergencia y ruta de atención en caso de accidente o incidente.</td>
                                    <td>Emergencias y primeros auxilios</td>
                                    <td><input type="text" name="f12c_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f12c_frec_5" placeholder="..."></td>
                                    <td><input type="text" name="f12c_evidencia_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>Registro de incidentes, accidentes o condiciones inseguras identificadas durante la actividad.</td>
                                    <td>Seguimiento SG-SST</td>
                                    <td><input type="text" name="f12c_resp_6" placeholder="..."></td>
                                    <td><input type="text" name="f12c_frec_6" placeholder="..."></td>
                                    <td><input type="text" name="f12c_evidencia_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>Medidas de seguridad vial para transporte de productos, insumos o desplazamientos a ferias y puntos de venta.</td>
                                    <td>Accidentes de tránsito</td>
                                    <td><input type="text" name="f12c_resp_7" placeholder="..."></td>
                                    <td><input type="text" name="f12c_frec_7" placeholder="..."></td>
                                    <td><input type="text" name="f12c_evidencia_7" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 5: COMERCIAL ================= -->
            <div class="form-section-card" id="step-5">
                <div class="section-header">
                    <h2>Módulo 5: Comercialización, Fidelización y QR</h2>
                    <p>Canales de venta, fijación de precios, logística rural y trazabilidad digital.</p>
                </div>

                                <!-- F13 Canales de venta -->
                <div class="format-block">
                    <div class="format-title"><span>F13</span> Canales de Comercialización</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f13">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Canal</th>
                                    <th style="width: 10%;">¿Aplica?</th>
                                    <th>¿Cuáles / Detalles?</th>
                                    <th>Frecuencia</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Ferias campesinas</strong></td>
                                    <td><select name="f13_aplica_0"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_0" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Venta directa</strong></td>
                                    <td><select name="f13_aplica_1"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_1" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Redes sociales</strong></td>
                                    <td><select name="f13_aplica_2"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_2" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>WhatsApp / llamadas</strong></td>
                                    <td><select name="f13_aplica_3"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_3" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Tiendas / restaurantes / plazas</strong></td>
                                    <td><select name="f13_aplica_4"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_4" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Compras institucionales</strong></td>
                                    <td><select name="f13_aplica_5"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_5" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_5" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comercialización asociativa</strong></td>
                                    <td><select name="f13_aplica_6"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_6" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_6" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Otro</strong></td>
                                    <td><select name="f13_aplica_7"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_7" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_7" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_7" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F14 Estrategia de Precios -->
                <div class="format-block">
                    <div class="format-title"><span>F14</span> Estrategia de Precios (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f14">
                            <thead>
                                <tr>
                                    <th>Producto / servicio</th>
                                    <th>Costo por unidad</th>
                                    <th>Margen deseado %</th>
                                    <th>Precio mínimo</th>
                                    <th>Precio de mercado</th>
                                    <th>Costo logístico</th>
                                    <th>Precio de venta final</th>
                                    <th>Justificación</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF14()">+ Añadir Producto</button>
                </div>

                <!-- F15 Ventas y Trazabilidad -->
                <div class="format-block">
                    <div class="format-title"><span>F15</span> Proyección de Ventas (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15">
                            <thead>
                                <tr>
                                    <th>Producto / servicio</th>
                                    <th>Cantidad vendida mensual</th>
                                    <th>Precio unitario</th>
                                    <th>Ingresos mensuales</th>
                                    <th>Forma de pago</th>
                                    <th>Cliente o canal principal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF15()">+ Añadir Venta</button>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F15A</span> Fidelización</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15a">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Tipo de cliente o comprador</th>
                                    <th>Estrategia de fidelización</th>
                                    <th>Medio de comunicación</th>
                                    <th>Frecuencia de contacto</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Cliente directo</strong></td>
                                    <td><input type="text" name="f15a_est_0" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_0" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comprador local</strong></td>
                                    <td><input type="text" name="f15a_est_1" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_1" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comprador institucional</strong></td>
                                    <td><input type="text" name="f15a_est_2" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_2" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Consumidor final</strong></td>
                                    <td><input type="text" name="f15a_est_3" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_3" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Aliado comercial</strong></td>
                                    <td><input type="text" name="f15a_est_4" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_4" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_4" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F15B</span> Logística de Última Milla (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15b">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Canal de entrega</th>
                                    <th>Tiempo máximo de entrega</th>
                                    <th>Medio de transporte</th>
                                    <th>Condición para mantener frescura</th>
                                    <th>Capacidad de carga</th>
                                    <th>Costo estimado</th>
                                    <th>Responsable</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF15B()">+ Añadir Logística</button>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F15C</span> Trazabilidad Digital</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15c">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Elemento de trazabilidad</th>
                                    <th style="width: 30%;">Información que debe contener</th>
                                    <th>Responsable de actualización</th>
                                    <th>Medio o plataforma</th>
                                    <th>Frecuencia de actualización</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Código QR en empaque o ficha técnica</strong></td>
                                    <td>Origen del producto, vereda, productor, fecha de cosecha o elaboración</td>
                                    <td><input type="text" name="f15c_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_0" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Ficha técnica digital</strong></td>
                                    <td>Descripción, unidad de medida, condiciones de uso, almacenamiento y diferencial</td>
                                    <td><input type="text" name="f15c_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_1" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Prácticas sostenibles</strong></td>
                                    <td>Uso eficiente del agua, manejo de residuos, compostaje, empaques biodegradables</td>
                                    <td><input type="text" name="f15c_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_2" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Lote o fecha de producción</strong></td>
                                    <td>Información para seguimiento de calidad e inocuidad</td>
                                    <td><input type="text" name="f15c_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_3" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Contacto comercial</strong></td>
                                    <td>WhatsApp, redes, correo o punto de venta</td>
                                    <td><input type="text" name="f15c_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_4" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_4" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 6: FINANZAS ================= -->
            <div class="form-section-card" id="step-6">
                <div class="section-header">
                    <h2>Módulo 6: Estructura Financiera y Viabilidad</h2>
                    <p>Inversiones requeridas, costos de operación recurrentes y proyección de flujos mensuales.</p>
                </div>

                                <!-- F16 Inversion -->
                <div class="format-block">
                    <div class="format-title"><span>F16</span> Inversión Inicial Necesaria</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f16">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Tipo de activo o recurso</th>
                                    <th>Descripción</th>
                                    <th style="width: 15%;">Valor unitario ($)</th>
                                    <th style="width: 10%;">Cantidad</th>
                                    <th style="width: 15%;">Valor total ($)</th>
                                    <th>Requisito técnico o ambiental</th>
                                    <th>Fuente de financiación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Maquinaria y equipo</strong></td>
                                    <td><input type="text" name="f16_desc_0" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_0" placeholder="..." oninput="calcInv(0)"></td>
                                    <td><input type="number" name="f16_cant_0" placeholder="..." oninput="calcInv(0)"></td>
                                    <td><input type="number" name="f16_total_0" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_0" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Infraestructura / adecuaciones</strong></td>
                                    <td><input type="text" name="f16_desc_1" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_1" placeholder="..." oninput="calcInv(1)"></td>
                                    <td><input type="number" name="f16_cant_1" placeholder="..." oninput="calcInv(1)"></td>
                                    <td><input type="number" name="f16_total_1" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_1" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Muebles y enseres</strong></td>
                                    <td><input type="text" name="f16_desc_2" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_2" placeholder="..." oninput="calcInv(2)"></td>
                                    <td><input type="number" name="f16_cant_2" placeholder="..." oninput="calcInv(2)"></td>
                                    <td><input type="number" name="f16_total_2" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_2" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Herramientas y varios</strong></td>
                                    <td><input type="text" name="f16_desc_3" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_3" placeholder="..." oninput="calcInv(3)"></td>
                                    <td><input type="number" name="f16_cant_3" placeholder="..." oninput="calcInv(3)"></td>
                                    <td><input type="number" name="f16_total_3" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_3" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Equipos de comunicación y TIC</strong></td>
                                    <td><input type="text" name="f16_desc_4" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_4" placeholder="..." oninput="calcInv(4)"></td>
                                    <td><input type="number" name="f16_cant_4" placeholder="..." oninput="calcInv(4)"></td>
                                    <td><input type="number" name="f16_total_4" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_4" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Empaques o sistema QR</strong></td>
                                    <td><input type="text" name="f16_desc_5" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_5" placeholder="..." oninput="calcInv(5)"></td>
                                    <td><input type="number" name="f16_cant_5" placeholder="..." oninput="calcInv(5)"></td>
                                    <td><input type="number" name="f16_total_5" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_5" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Gastos preoperativos</strong></td>
                                    <td><input type="text" name="f16_desc_6" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_6" placeholder="..." oninput="calcInv(6)"></td>
                                    <td><input type="number" name="f16_cant_6" placeholder="..." oninput="calcInv(6)"></td>
                                    <td><input type="number" name="f16_total_6" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_6" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Acciones ambientales iniciales</strong></td>
                                    <td><input type="text" name="f16_desc_7" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_7" placeholder="..." oninput="calcInv(7)"></td>
                                    <td><input type="number" name="f16_cant_7" placeholder="..." oninput="calcInv(7)"></td>
                                    <td><input type="number" name="f16_total_7" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_7" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Registros y Permisos</strong></td>
                                    <td><input type="text" name="f16_desc_8" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_8" placeholder="..." oninput="calcInv(8)"></td>
                                    <td><input type="number" name="f16_cant_8" placeholder="..." oninput="calcInv(8)"></td>
                                    <td><input type="number" name="f16_total_8" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_8" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_8" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F17 Costos -->
                <div class="format-block">
                    <div class="format-title"><span>F17</span> Costos Mensuales del Negocio</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f17">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Tipo de costo</th>
                                    <th>Descripción</th>
                                    <th style="width: 20%;">Valor mensual ($)</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Costos fijos</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Pagos obligatorios constantes que no cambian según la cantidad producida (ej. arriendos, servicios, salarios base).
                                        </div>
                                    </td>
                                    <td><input type="text" name="f17_desc_0" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_0" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Costos variables</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Gastos directos que aumentan o disminuyen según la cantidad de unidades que vayas a producir (ej. materias primas, insumos, jornales temporales).
                                        </div>
                                    </td>
                                    <td><input type="text" name="f17_desc_1" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_1" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Costos logísticos</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Gastos relacionados con el traslado, distribución de productos, transporte de insumos y costos de fletes o combustibles.
                                        </div>
                                    </td>
                                    <td><input type="text" name="f17_desc_2" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_2" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Costos de empaque / etiqueta / QR</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Inversión en los envases, botellas, bolsas, impresión de etiquetas y códigos QR para la presentación del producto.
                                        </div>
                                    </td>
                                    <td><input type="text" name="f17_desc_3" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_3" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Costos ambientales</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Gastos en prácticas sostenibles, como elaboración de abonos orgánicos (compostaje), manejo de residuos o tecnologías de ahorro de agua.
                                        </div>
                                    </td>
                                    <td><input type="text" name="f17_desc_4" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_4" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Costos digitales</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Gastos para comercialización y visibilidad virtual (ej. plan de datos, saldo de internet, publicidad en redes, diseño de marca).
                                        </div>
                                    </td>
                                    <td><input type="text" name="f17_desc_5" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_5" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_5" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F18 Flujo de caja simple -->
                <div class="format-block">
                    <div class="format-title"><span>F18</span> Flujo de Caja Proyectado (Primer Semestre)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f18">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Ingresos por Ventas ($)</th>
                                    <th>Gastos de Producción ($)</th>
                                    <th>Gastos Ambientales ($)</th>
                                    <th>Gastos Logísticos ($)</th>
                                    <th>Balance Neto ($)</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Mes 1</strong></td>
                                    <td><input type="number" name="f18_ingreso_m1" placeholder="Ej. 2000000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_gprod_m1" placeholder="Ej. 1200000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_gamb_m1" placeholder="Ej. 100000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_glog_m1" placeholder="Ej. 200000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_neto_m1" readonly placeholder="Calculado"></td>
                                    <td><input type="text" name="f18_obs_m1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 2</strong></td>
                                    <td><input type="number" name="f18_ingreso_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_gprod_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_gamb_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_glog_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_neto_m2" readonly></td>
                                    <td><input type="text" name="f18_obs_m2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 3</strong></td>
                                    <td><input type="number" name="f18_ingreso_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_gprod_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_gamb_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_glog_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_neto_m3" readonly></td>
                                    <td><input type="text" name="f18_obs_m3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 4</strong></td>
                                    <td><input type="number" name="f18_ingreso_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_gprod_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_gamb_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_glog_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_neto_m4" readonly></td>
                                    <td><input type="text" name="f18_obs_m4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 5</strong></td>
                                    <td><input type="number" name="f18_ingreso_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_gprod_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_gamb_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_glog_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_neto_m5" readonly></td>
                                    <td><input type="text" name="f18_obs_m5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 6</strong></td>
                                    <td><input type="number" name="f18_ingreso_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_gprod_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_gamb_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_glog_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_neto_m6" readonly></td>
                                    <td><input type="text" name="f18_obs_m6" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 7: SOSTENIBILIDAD ================= -->
            <div class="form-section-card" id="step-7">
                <div class="section-header">
                    <h2>Módulo 7: Sostenibilidad Regenerativa y Manejo Ambiental</h2>
                    <p>Medición de huellas ecológicas, planes de compostaje, economía circular y evaluación regenerativa del páramo.</p>
                </div>

                                <!-- F19 Huellas -->
                <div class="format-block">
                    <div class="format-title"><span>F19</span> Indicadores de Huella Hídrica y de Carbono</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f19">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Indicador</th>
                                    <th style="width: 25%;">Qué se mide</th>
                                    <th>Dato inicial</th>
                                    <th>Meta de reducción o mejora</th>
                                    <th>Frecuencia de medición</th>
                                    <th>Responsable</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Consumo de agua</strong></td>
                                    <td>Litros usados por ciclo, mes o unidad de producto</td>
                                    <td><input type="text" name="f19_ini_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Fuente de agua</strong></td>
                                    <td>Origen del agua y medidas de protección</td>
                                    <td><input type="text" name="f19_ini_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso de energía</strong></td>
                                    <td>Consumo mensual o uso de combustibles</td>
                                    <td><input type="text" name="f19_ini_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Transporte</strong></td>
                                    <td>Distancia recorrida y tipo de transporte para ventas o insumos</td>
                                    <td><input type="text" name="f19_ini_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso de insumos</strong></td>
                                    <td>Cantidad y tipo de insumos orgánicos o convencionales</td>
                                    <td><input type="text" name="f19_ini_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Estimación de huella de carbono</strong></td>
                                    <td>Emisiones asociadas a energía, transporte e insumos</td>
                                    <td><input type="text" name="f19_ini_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Estimación de huella hídrica</strong></td>
                                    <td>Agua usada por unidad de producto o ciclo productivo</td>
                                    <td><input type="text" name="f19_ini_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_6" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f19_conclusion">Conclusión de datos obtenidos</label>
                        <textarea id="f19_conclusion" name="f19_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F19A</span> Variable Hídrica</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f19a">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Variable hídrica</th>
                                    <th style="width: 25%;">Descripción / estado</th>
                                    <th>¿Cuántas? (Si aplica, o "No aplica")</th>
                                    <th>Impacto en la producción (1-5)</th>
                                    <th>Acción de mejora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Reutilización del agua</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Reutiliza el agua en alguna actividad de la finca? ¿Cuál?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_0" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_0" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_0" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Cercanía a fuentes hídricas</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿En el lugar hay quebradas, nacederos, humedales o lagunas?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_1" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_1" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_1" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Vegetación en fuentes</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Si contestó Sí a la pregunta anterior, ¿las quebradas o nacederos tienen árboles o vegetación alrededor?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_2" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_2" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_2" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Disponibilidad del recurso hídrico</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Durante todo el año tiene suficiente agua para la producción?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_3" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_3" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_3" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Acción regenerativa (Siembra)</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Ha sembrado árboles cerca de las fuentes de agua como acción regenerativa?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_4" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_4" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_4" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Uso en el proceso productivo</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Para qué actividades utiliza el agua? (riego, animales, lavado, transformación, consumo humano).
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_5" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_5" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_5" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Riesgo de contaminación</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Hay actividades que puedan contaminar el agua?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_6" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_6" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_6" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Consumo en el proceso productivo</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Considera que usa mucha, poca o suficiente agua para producir?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_7" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_7" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_7" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Dependencia de fuentes externas</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿El agua que utiliza proviene del mismo predio o debe traerla de otro lugar?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_8" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_8" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_8" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_8" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Aprovechamiento de agua lluvia</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Recoge agua lluvia para usarla en el predio? ¿Cómo? (canecas, pocetas, albercas, tanques con geomembrana u otros sistemas).
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_9" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_9" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_9" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_9" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>¿El predio cuenta con bebederos?</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Los animales cuentan con bebederos para evitar entrar directamente a las quebradas o nacederos?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_10" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_10" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_10" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_10" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>¿Se cuenta con equipos eficientes de riego?</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Utiliza algún sistema que ayude a ahorrar agua al regar?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_11" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_11" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_11" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_11" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Problemas por disminución de agua</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            ¿Ha tenido problemas porque el agua disminuye en verano?
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_12" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_12" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_12" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_12" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Cuidado del agua de la finca</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Se pueden evidenciar prácticas como cercas, siembra de árboles, protección de nacederos, uso eficiente y mantenimiento de mangueras.
                                        </div>
                                    </td>
                                    <td><input type="text" name="f19a_desc_13" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_13" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_13" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_13" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="text-align: right;"><strong>Total</strong></td>
                                    <td><input type="number" id="f19a_total_impacto" readonly placeholder="Sumatoria"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f19a_conclusion">Conclusión de resultados obtenidos</label>
                        <textarea id="f19a_conclusion" name="f19a_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <!-- F20 Economia Circular -->
                <div class="format-block">
                    <div class="format-title"><span>F20</span> Manejo de residuos, subproductos y empaques</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f20">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Residuo, subproducto o insumo</th>
                                    <th>Cantidad estimada de uso (kg)</th>
                                    <th>Manejo actual</th>
                                    <th style="width: 25%;">Acción circular propuesta</th>
                                    <th>Destino final</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Residuos orgánicos de cosecha</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            (Ejm: pasto, hojas, residuos de cocina, restos de cosecha cocina o material vegetal).
                                        </div>
                                    </td>
                                    <td><input type="number" name="f20_cant_0" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_0" placeholder="..."></td>
                                    <td>Compostaje, lombricultura, reincorporación al suelo.</td>
                                    <td><input type="text" name="f20_destino_0" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Residuos aprovechables</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            Plástico, vidrios, botellas, empaques y materiales reciclables.
                                        </div>
                                    </td>
                                    <td><input type="number" name="f20_cant_1" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_1" placeholder="..."></td>
                                    <td>Uso de empaques biodegradables, empaques retornables y reciclables.</td>
                                    <td><input type="text" name="f20_destino_1" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Residuos no aprovechables</strong></td>
                                    <td><input type="number" name="f20_cant_2" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_2" placeholder="..."></td>
                                    <td>Separación y disposición responsable.</td>
                                    <td><input type="text" name="f20_destino_2" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Subproductos con valor comercial</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            (materiales que pueden venderse o aprovecharse, como estiércol, leche de descarte, lana, madera u otros.)
                                        </div>
                                    </td>
                                    <td><input type="number" name="f20_cant_3" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_3" placeholder="..."></td>
                                    <td>Transformación o venta asociativa.</td>
                                    <td><input type="text" name="f20_destino_3" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Residuo peligroso</strong>
                                        <div style="font-size: 0.78rem; color: #555; font-weight: normal; margin-top: 4px; line-height: 1.3;">
                                            (envases de agroquímicos, aceites usados, pilas, medicamentos veterinarios vencidos y otros residuos peligrosos.)
                                        </div>
                                    </td>
                                    <td><input type="number" name="f20_cant_4" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_4" placeholder="..."></td>
                                    <td>Separación, almacenamiento temporal seguro y entrega a gestores o puntos autorizados.</td>
                                    <td><input type="text" name="f20_destino_4" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Total de cantidad estimada</strong></td>
                                    <td><input type="number" id="f20_total_cant" readonly placeholder="Calculado"></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f20_conclusion">Conclusión de datos obtenidos</label>
                        <textarea id="f20_conclusion" name="f20_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <!-- F21 Evaluacion Regenerativa -->
                <div class="format-block">
                    <div class="format-title"><span>F21</span> Evaluación Ambiental Regenerativa</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f21">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Factor</th>
                                    <th style="width: 15%;">Indicador</th>
                                    <th style="width: 25%;">Descripción</th>
                                    <th>Estado actual</th>
                                    <th style="width: 10%;">Calificación 1-5</th>
                                    <th>Acción de mejora</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Agua</strong></td>
                                    <td>Uso eficiente</td>
                                    <td>Prácticas de ahorro, riego eficiente, control de desperdicio</td>
                                    <td><input type="text" name="f21_estado_0" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_0" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_0" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Agua</strong></td>
                                    <td>Protección de fuentes</td>
                                    <td>Conservación de nacimientos, rondas hídricas y zonas de recarga</td>
                                    <td><input type="text" name="f21_estado_1" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_1" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_1" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Suelo</strong></td>
                                    <td>Conservación</td>
                                    <td>Cobertura vegetal, rotación, abonos orgánicos, prevención de compactación</td>
                                    <td><input type="text" name="f21_estado_2" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_2" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_2" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Suelo</strong></td>
                                    <td>Erosión</td>
                                    <td>Control de escorrentía, barreras vivas, manejo de pendientes</td>
                                    <td><input type="text" name="f21_estado_3" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_3" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_3" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Biodiversidad</strong></td>
                                    <td>Protección ecosistémica</td>
                                    <td>Conservación de flora, fauna, cercas vivas, no expansión a zonas sensibles</td>
                                    <td><input type="text" name="f21_estado_4" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_4" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_4" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Energía</strong></td>
                                    <td>Uso eficiente</td>
                                    <td>Ahorro energético o alternativas limpias</td>
                                    <td><input type="text" name="f21_estado_5" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_5" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_5" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Residuos</strong></td>
                                    <td>Manejo adecuado</td>
                                    <td>Separación, reciclaje, compostaje y disposición final</td>
                                    <td><input type="text" name="f21_estado_6" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_6" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_6" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Insumos</strong></td>
                                    <td>Producción limpia</td>
                                    <td>Uso de insumos orgánicos o de bajo impacto</td>
                                    <td><input type="text" name="f21_estado_7" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_7" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_7" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Normativa</strong></td>
                                    <td>Cumplimiento responsable</td>
                                    <td>Conocimiento y aplicación de permisos, restricciones y buenas prácticas</td>
                                    <td><input type="text" name="f21_estado_8" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_8" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_8" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_8" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="text-align: right;"><strong>Total Calificación</strong></td>
                                    <td><input type="number" id="f21_total_cal" readonly placeholder="Calculado"></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f21_conclusion">Resultados obtenidos</label>
                        <textarea id="f21_conclusion" name="f21_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <!-- F22 Plan Ambiental -->
                <div class="format-block">
                    <div class="format-title"><span>F22</span> Plan de Acción y Mitigación Ambiental</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f22">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Impacto identificado</th>
                                    <th>Acción preventiva o correctiva</th>
                                    <th>Plazo</th>
                                    <th>Responsable</th>
                                    <th>Recursos necesarios</th>
                                    <th>Indicador</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Uso excesivo de agua</strong></td>
                                    <td><input type="text" name="f22_accion_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Afectación de fuentes hídricas</strong></td>
                                    <td><input type="text" name="f22_accion_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Degradación del suelo</strong></td>
                                    <td><input type="text" name="f22_accion_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Generación de residuos</strong></td>
                                    <td><input type="text" name="f22_accion_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso inadecuado de insumos</strong></td>
                                    <td><input type="text" name="f22_accion_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Pérdida de biodiversidad</strong></td>
                                    <td><input type="text" name="f22_accion_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Emisiones por transporte</strong></td>
                                    <td><input type="text" name="f22_accion_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_6" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F22A Adaptación al Cambio Climático -->
                <div class="format-block">
                    <div class="format-title"><span>F22A</span> Adaptación al Cambio Climático</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f22a">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Aspecto</th>
                                    <th>Respuesta</th>
                                    <th style="width: 15%;">Nivel de riesgo</th>
                                    <th>Acción de mejora propuesta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>¿En los últimos años ha tenido problemas por heladas, sequías, lluvias fuertes o granizadas? ¿Cuáles?</strong></td>
                                    <td><input type="text" name="f22a_resp_0" placeholder="..."></td>
                                    <td>
                                        <select name="f22a_riesgo_0">
                                            <option value="Bajo">Bajo</option>
                                            <option value="Medio">Medio</option>
                                            <option value="Alto">Alto</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f22a_mejora_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>¿Qué cultivos, animales o actividades de la finca se han visto más afectados?</strong></td>
                                    <td><input type="text" name="f22a_resp_1" placeholder="..."></td>
                                    <td>
                                        <select name="f22a_riesgo_1">
                                            <option value="Bajo">Bajo</option>
                                            <option value="Medio">Medio</option>
                                            <option value="Alto">Alto</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f22a_mejora_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>¿Qué hace para proteger su producción cuando ocurren estos eventos?</strong></td>
                                    <td><input type="text" name="f22a_resp_2" placeholder="..."></td>
                                    <td>
                                        <select name="f22a_riesgo_2">
                                            <option value="Bajo">Bajo</option>
                                            <option value="Medio">Medio</option>
                                            <option value="Alto">Alto</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f22a_mejora_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>¿Ha cambiado la fecha de siembra, cosecha o el manejo de los animales por el clima?</strong></td>
                                    <td><input type="text" name="f22a_resp_3" placeholder="..."></td>
                                    <td>
                                        <select name="f22a_riesgo_3">
                                            <option value="Bajo">Bajo</option>
                                            <option value="Medio">Medio</option>
                                            <option value="Alto">Alto</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f22a_mejora_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>¿Cuenta con alguna reserva de agua para épocas de verano o sequía (tanques, albercas, reservorios, agua lluvia)?</strong></td>
                                    <td><input type="text" name="f22a_resp_4" placeholder="..."></td>
                                    <td>
                                        <select name="f22a_riesgo_4">
                                            <option value="Bajo">Bajo</option>
                                            <option value="Medio">Medio</option>
                                            <option value="Alto">Alto</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f22a_mejora_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>¿Qué considera que necesita para adaptarse mejor a estos cambios del clima?</strong></td>
                                    <td><input type="text" name="f22a_resp_5" placeholder="..."></td>
                                    <td>
                                        <select name="f22a_riesgo_5">
                                            <option value="Bajo">Bajo</option>
                                            <option value="Medio">Medio</option>
                                            <option value="Alto">Alto</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f22a_mejora_5" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f22a_conclusion">Conclusiones</label>
                        <textarea id="f22a_conclusion" name="f22a_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 8: RIESGOS ================= -->
            <div class="form-section-card" id="step-8">
                <div class="section-header">
                    <h2>Módulo 8: Matriz de Riesgos, Indicadores y Coherencia</h2>
                    <p>Análisis de riesgos, plan de acción final, indicadores integrales de gestión y coherencia sistémica.</p>
                </div>

                                <!-- F23 Riesgos -->
                <div class="format-block">
                    <div class="format-title"><span>F23</span> Matriz de Riesgos y Mitigación</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f23">
                            <thead>
                                <tr>
                                    <th>Tipo de riesgo</th>
                                    <th>Riesgo identificado</th>
                                    <th>Causa</th>
                                    <th>Consecuencia</th>
                                    <th style="width: 10%;">Nivel de riesgo</th>
                                    <th>Acción de prevención</th>
                                    <th>Acción de respuesta</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="dynamic-row-f23">
                                    <td>
                                        <select name="f23_tipo[]" onchange="handleF23TypeChange(this)" style="width: 100%;">
                                            <option value="Comercial (Baja demanda)">Comercial (Baja demanda)</option>
                                            <option value="Productivo (Baja producción)">Productivo (Baja producción)</option>
                                            <option value="Ambiental (Afectación de fuentes hídricas)">Ambiental (Afectación de fuentes hídricas)</option>
                                            <option value="Ambiental (Deterioro del suelo)">Ambiental (Deterioro del suelo)</option>
                                            <option value="Climático (Heladas, lluvias intensas o sequías)">Climático (Heladas, lluvias intensas o sequías)</option>
                                            <option value="Logístico (Pérdida de frescura o retraso en entregas)">Logístico (Pérdida de frescura o retraso en entregas)</option>
                                            <option value="Digital (Falta de actualización del QR, redes o base de datos)">Digital (Falta de actualización del QR, redes o base de datos)</option>
                                            <option value="Organizativo (Falta de cooperación entre productores)">Organizativo (Falta de cooperación entre productores)</option>
                                            <option value="Financiero (Aumento de costos)">Financiero (Aumento de costos)</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f23_riesgo[]" placeholder="Ej. Baja demanda"></td>
                                    <td><input type="text" name="f23_causa[]" placeholder="..."></td>
                                    <td><input type="text" name="f23_consecuencia[]" placeholder="..."></td>
                                    <td>
                                        <select name="f23_nivel[]">
                                            <option value="Alto">Alto</option>
                                            <option value="Medio" selected>Medio</option>
                                            <option value="Bajo">Bajo</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f23_prevencion[]" placeholder="..."></td>
                                    <td><input type="text" name="f23_respuesta[]" placeholder="..."></td>
                                    <td>
                                        <button type="button" class="row-action-btn" onclick="removeRow(this)">
                                            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF23()">+ Añadir Riesgo</button>
                </div>

                <!-- F24 Plan de accion final -->
                <div class="format-block">
                    <div class="format-title"><span>F24</span> Plan de Acción de Fortalecimiento</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f24">
                            <thead>
                                <tr>
                                    <th>Actividad</th>
                                    <th style="width: 15%;">Componente</th>
                                    <th>Responsable</th>
                                    <th>Tiempo</th>
                                    <th>Recursos necesarios</th>
                                    <th>Resultado esperado</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="f24_act_0" placeholder="..."></td>
                                    <td><strong>Productivo</strong></td>
                                    <td><input type="text" name="f24_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_1" placeholder="..."></td>
                                    <td><strong>Comercial</strong></td>
                                    <td><input type="text" name="f24_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_2" placeholder="..."></td>
                                    <td><strong>Financiero</strong></td>
                                    <td><input type="text" name="f24_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_3" placeholder="..."></td>
                                    <td><strong>Ambiental</strong></td>
                                    <td><input type="text" name="f24_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_4" placeholder="..."></td>
                                    <td><strong>Digital / trazabilidad</strong></td>
                                    <td><input type="text" name="f24_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_5" placeholder="..."></td>
                                    <td><strong>Cooperación territorial</strong></td>
                                    <td><input type="text" name="f24_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_5" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F25 Indicadores -->
                <div class="format-block">
                    <div class="format-title"><span>F25</span> Indicadores de Gestión Integrales</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f25">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Dimensión</th>
                                    <th>Indicador</th>
                                    <th>Meta</th>
                                    <th>Frecuencia</th>
                                    <th>Responsable</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Ventas</strong></td>
                                    <td><input type="text" name="f25_ind_0" value="Ingresos mensuales / unidades vendidas"></td>
                                    <td><input type="text" name="f25_meta_0" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Producción</strong></td>
                                    <td><input type="text" name="f25_ind_1" value="Cantidad producida vs capacidad recomendada"></td>
                                    <td><input type="text" name="f25_meta_1" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Financiera</strong></td>
                                    <td><input type="text" name="f25_ind_2" value="Balance mensual y margen"></td>
                                    <td><input type="text" name="f25_meta_2" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Cliente</strong></td>
                                    <td><input type="text" name="f25_ind_3" value="Número de clientes recurrentes o recompra"></td>
                                    <td><input type="text" name="f25_meta_3" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Digital</strong></td>
                                    <td><input type="text" name="f25_ind_4" value="Productos con QR / consultas / actualizaciones"></td>
                                    <td><input type="text" name="f25_meta_4" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Logística</strong></td>
                                    <td><input type="text" name="f25_ind_5" value="Entregas puntuales y productos frescos"></td>
                                    <td><input type="text" name="f25_meta_5" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_5" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Ambiental</strong></td>
                                    <td><input type="text" name="f25_ind_6" value="Reducción de consumo de agua o mejor manejo de residuos"></td>
                                    <td><input type="text" name="f25_meta_6" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_6" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_6" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Regenerativa</strong></td>
                                    <td><input type="text" name="f25_ind_7" value="Compost producido, suelo mejorado, fuentes protegidas"></td>
                                    <td><input type="text" name="f25_meta_7" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_7" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_7" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Cooperación</strong></td>
                                    <td><input type="text" name="f25_ind_8" value="Alianzas activas o ventas asociativas"></td>
                                    <td><input type="text" name="f25_meta_8" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_8" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_8" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_8" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F26 Coherencia -->
                <div class="format-block">
                    <div class="format-title"><span>F26</span> Matriz de Coherencia Sistémica del PMAPC</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f26">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Decisión del negocio</th>
                                    <th>Efecto productivo</th>
                                    <th>Efecto comercial</th>
                                    <th>Efecto financiero</th>
                                    <th>Efecto ambiental</th>
                                    <th>Ajuste necesario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Aumentar producción</strong></td>
                                    <td><input type="text" name="f26_prod_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Cambiar insumos</strong></td>
                                    <td><input type="text" name="f26_prod_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Ampliar canales de venta</strong></td>
                                    <td><input type="text" name="f26_prod_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comprar maquinaria</strong></td>
                                    <td><input type="text" name="f26_prod_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Transformar producto</strong></td>
                                    <td><input type="text" name="f26_prod_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Participar en ferias</strong></td>
                                    <td><input type="text" name="f26_prod_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Implementar QR</strong></td>
                                    <td><input type="text" name="f26_prod_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Usar empaques biodegradables</strong></td>
                                    <td><input type="text" name="f26_prod_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Asociarse con otros productores</strong></td>
                                    <td><input type="text" name="f26_prod_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_8" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-grid" style="margin-top: 15px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f26_coherencia">Análisis Adicional de Coherencia</label>
                            <textarea id="f26_coherencia" name="f26_coherencia" rows="3" placeholder="..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="nav-buttons-container">
                <button type="button" class="btn-nav btn-prev" id="btn-prev" onclick="changeStep(-1)" style="display: none;">
                    ← Anterior
                </button>
                <button type="button" class="btn-nav btn-next" id="btn-next" onclick="changeStep(1)">
                    Siguiente →
                </button>
            </div>
        </form>
    </main>

    <!-- Custom Success Modal -->
    <div id="success-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon">
                <img src="assets/logo_somossumapaz.png" alt="Exito" style="width: 80px; height: auto;">
            </div>
            <h2 class="serif modal-title" id="success-title">¡Guardado Exitoso!</h2>
            <p class="modal-text" id="success-message">El Plan de Manejo Ambiental, Productivo y Comercial (PMAPC) ha sido guardado correctamente en la base de datos.</p>
            <button id="modal-close-btn" class="btn btn-submit" style="width: 100%; margin-top: 1.5rem;">Aceptar</button>
        </div>
    </div>

    <!-- Custom Error Modal -->
    <div id="error-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon" style="background: #444F2F;">
                <img src="assets/logo_somossumapaz.png" alt="Error" style="width: 80px; height: auto; filter: grayscale(100%);">
            </div>
            <h2 class="serif modal-title" style="color: #D32F2F;">Aviso</h2>
            <p id="error-modal-text" class="modal-text">Ocurrió un error inesperado al guardar el PMAPC.</p>
            <button id="error-close-btn" class="btn btn-submit" style="width: 100%; margin-top: 1.5rem; background-color: #D32F2F;">Cerrar</button>
        </div>
    </div>

    <!-- App Logic -->
    <script>
        const API_BASE = window.location.protocol === 'file:' ? 'https://productorescampesinos.com/' : '';
        let currentStep = 1;
        const totalSteps = 8;
        let allProducers = [];
        let selectedProducerId = null;

        document.addEventListener('DOMContentLoaded', async () => {
            // Check Auth Status on Load
            try {
                const res = await fetch(API_BASE + 'api/check_auth.php');
                const result = await res.json();
                if (res.ok && result.authenticated) {
                    document.getElementById('header-auth-container').style.display = 'flex';
                }
            } catch (error) {
                console.error('Error checking auth:', error);
            }

            // Load registered producers
            await loadProducers();

            // Check URL query parameters
            const urlParams = new URLSearchParams(window.location.search);
            const idParam = urlParams.get('id');

            if (idParam) {
                selectedProducerId = idParam;
                document.getElementById('select-producer').value = idParam;
                document.getElementById('productor_id').value = idParam;
                document.getElementById('producer-selector-box').style.display = 'none';
                
                // Show prefilled card
                const prod = allProducers.find(p => p.id == idParam);
                if (prod) {
                    document.getElementById('select-producer-input').value = prod.nombre_completo;
                    showProducerDetails(prod);
                }
                
                // Fetch saved PMAPC
                await loadSavedPmapc(idParam);
            }

            // Search and autocomplete logic
            const searchInput = document.getElementById('select-producer-input');
            const resultsDiv = document.getElementById('select-producer-results');

            if (searchInput && resultsDiv) {
                const renderMatches = (query = '') => {
                    const queryClean = query.toLowerCase().trim();
                    const filtered = queryClean === '' ? allProducers : allProducers.filter(p => {
                        return p.nombre_completo.toLowerCase().includes(queryClean) || 
                               (p.numero_documento && p.numero_documento.includes(queryClean));
                    });

                    resultsDiv.innerHTML = '';
                    if (filtered.length === 0) {
                        resultsDiv.innerHTML = '<div style="padding: 0.8rem 1rem; color: #888; font-size: 0.9rem;">No se encontraron beneficiarios.</div>';
                        resultsDiv.style.display = 'block';
                        return;
                    }

                    filtered.forEach(p => {
                        const div = document.createElement('div');
                        div.style.padding = '0.8rem 1rem';
                        div.style.cursor = 'pointer';
                        div.style.borderBottom = '1px solid #eee';
                        div.style.fontSize = '0.95rem';
                        div.style.transition = 'background-color 0.2s';
                        div.innerHTML = `<strong>${p.nombre_completo}</strong> <span style="color: #666; font-size: 0.85rem;">(${p.tipo_documento} ${p.numero_documento})</span>`;
                        
                        div.onmouseover = () => { div.style.backgroundColor = '#F0F2F5'; };
                        div.onmouseout = () => { div.style.backgroundColor = 'transparent'; };
                        
                        div.onclick = async () => {
                            searchInput.value = p.nombre_completo;
                            document.getElementById('select-producer').value = p.id;
                            selectedProducerId = p.id;
                            document.getElementById('productor_id').value = p.id;
                            showProducerDetails(p);
                            resultsDiv.style.display = 'none';
                            await loadSavedPmapc(p.id);
                        };
                        resultsDiv.appendChild(div);
                    });
                    resultsDiv.style.display = 'block';
                };

                searchInput.addEventListener('input', (e) => {
                    renderMatches(e.target.value);
                });

                searchInput.addEventListener('focus', (e) => {
                    renderMatches(e.target.value);
                });

                document.addEventListener('click', (e) => {
                    if (!e.target.closest('#producer-selector-box')) {
                        resultsDiv.style.display = 'none';
                    }
                });
            }

            // Listen for change producer click
            const btnChange = document.getElementById('btn-change-producer');
            if (btnChange) {
                btnChange.addEventListener('click', () => {
                    document.getElementById('producer-selector-box').style.display = 'block';
                    document.getElementById('producer-info-box').style.display = 'none';
                    document.getElementById('transcript-upload-box').style.display = 'none';
                    
                    // Reset transcript file input and labels
                    document.getElementById('transcript-file-input').value = '';
                    document.getElementById('selected-file-name').textContent = 'Ningún archivo seleccionado';
                    const processBtn = document.getElementById('btn-process-transcript');
                    processBtn.disabled = true;
                    processBtn.style.opacity = '0.5';
                    processBtn.style.cursor = 'not-allowed';
                    document.getElementById('ia-processing-status').style.display = 'none';

                    const progressCard = document.getElementById('form-progress-card');
                    if (progressCard) {
                        progressCard.style.display = 'none';
                    }
                    document.getElementById('select-producer').value = '';
                    document.getElementById('select-producer-input').value = '';
                    selectedProducerId = null;
                    document.getElementById('productor_id').value = '';
                    document.getElementById('pmapc-form').reset();
                    clearDynamicRows();
                    updateFormCompletionProgress();
                });
            }

            // Progress bar event listeners
            const form = document.getElementById('pmapc-form');
            if (form) {
                form.addEventListener('input', updateFormCompletionProgress);
                form.addEventListener('change', updateFormCompletionProgress);
            }
            const fastInputs = ['edit-vereda', 'edit-predio', 'edit-fecha-nacimiento', 'edit-telefono', 'edit-correo'];
            fastInputs.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updateFormCompletionProgress);
                    el.addEventListener('change', updateFormCompletionProgress);
                }
            });

            // Transcript file upload listeners
            const transcriptFileInput = document.getElementById('transcript-file-input');
            const btnProcessTranscript = document.getElementById('btn-process-transcript');
            const selectedFileName = document.getElementById('selected-file-name');
            const iaProcessingStatus = document.getElementById('ia-processing-status');
            const iaProcessingText = document.getElementById('ia-processing-text');
            
            let loadedTranscriptText = "";

            if (transcriptFileInput) {
                transcriptFileInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        selectedFileName.textContent = file.name;
                        
                        const reader = new FileReader();
                        reader.onload = (evt) => {
                            loadedTranscriptText = evt.target.result;
                            
                            // Enable action button
                            btnProcessTranscript.disabled = false;
                            btnProcessTranscript.style.opacity = '1';
                            btnProcessTranscript.style.cursor = 'pointer';
                        };
                        reader.readAsText(file);
                    } else {
                        selectedFileName.textContent = 'Ningún archivo seleccionado';
                        btnProcessTranscript.disabled = true;
                        btnProcessTranscript.style.opacity = '0.5';
                        btnProcessTranscript.style.cursor = 'not-allowed';
                        loadedTranscriptText = "";
                    }
                });
            }

            if (btnProcessTranscript) {
                btnProcessTranscript.addEventListener('click', async () => {
                    if (!loadedTranscriptText || !selectedProducerId) return;

                    // Disable buttons and show processing status
                    btnProcessTranscript.disabled = true;
                    btnProcessTranscript.style.opacity = '0.5';
                    btnProcessTranscript.style.cursor = 'not-allowed';
                    transcriptFileInput.disabled = true;
                    iaProcessingStatus.style.display = 'flex';
                    iaProcessingText.textContent = "La IA está analizando la entrevista. Por favor espera (esto puede tardar de 15 a 30 segundos)...";

                    try {
                        const response = await fetch(API_BASE + 'api/analyze_transcript.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                transcript: loadedTranscriptText
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Error al conectar con el servidor.');
                        }

                        const result = await response.json();
                        if (result.success && result.data) {
                            // Populate form with extracted data
                            populateForm(result.data);
                            
                            // Show success message
                            alert("¡Autocompletado con éxito! Todos los campos posibles han sido rellenados a partir de la entrevista. Revisa las 8 pestañas y haz clic en 'Guardar Plan de Manejo' al final para guardar los cambios.");
                        } else {
                            throw new Error(result.error || 'Ocurrió un error inesperado al analizar la entrevista.');
                        }
                    } catch (error) {
                        console.error('IA processing error:', error);
                        alert('Error al procesar la entrevista con la IA: ' + error.message);
                    } finally {
                        // Re-enable and reset status
                        btnProcessTranscript.disabled = false;
                        btnProcessTranscript.style.opacity = '1';
                        btnProcessTranscript.style.cursor = 'pointer';
                        transcriptFileInput.disabled = false;
                        iaProcessingStatus.style.display = 'none';
                    }
                });
            }

            // Close Modal Logic
            document.getElementById('modal-close-btn').addEventListener('click', function () {
                document.getElementById('success-modal').classList.remove('active');
                window.location.href = 'productores_registrados.html';
            });

            document.getElementById('error-close-btn').addEventListener('click', function () {
                document.getElementById('error-modal').classList.remove('active');
            });
        });

        async function loadProducers() {
            try {
                const res = await fetch(API_BASE + 'api/get_productores.php');
                const result = await res.json();
                
                if (result.success) {
                    allProducers = result.data;
                    localStorage.setItem('cached_productores', JSON.stringify(allProducers));
                    document.getElementById('select-producer-input').placeholder = "Escriba el nombre o documento para buscar...";
                } else {
                    throw new Error(result.error || "Error del servidor");
                }
            } catch (err) {
                console.error("Error loading producers:", err);
                // Fallback to cache
                const cached = localStorage.getItem('cached_productores');
                if (cached) {
                    allProducers = JSON.parse(cached);
                    document.getElementById('select-producer-input').placeholder = "Cargado de caché (Sin conexión)...";
                    showOfflineNotice("Lista de productores cargada desde la memoria local.");
                } else {
                    document.getElementById('select-producer-input').placeholder = "Error de red - Sin caché";
                }
            }
        }

        function showProducerDetails(p) {
            document.getElementById('lbl-nombre').textContent = p.nombre_completo;
            document.getElementById('lbl-documento').textContent = `${p.tipo_documento} ${p.numero_documento}`;
            document.getElementById('edit-vereda').value = p.vereda || '';
            document.getElementById('edit-predio').value = p.nombre_predio || '';
            document.getElementById('edit-fecha-nacimiento').value = p.fecha_nacimiento || '';
            document.getElementById('edit-telefono').value = p.telefono || '';
            document.getElementById('edit-correo').value = p.correo_electronico || '';
            
            // Autocomplete organization name in Step 1
            const orgInput = document.getElementById('f01_nombre_organizacion');
            if (orgInput) {
                orgInput.value = p.nombre_organizacion || '';
            }

            document.getElementById('producer-info-box').style.display = 'block';
            document.getElementById('transcript-upload-box').style.display = 'block';
            const progressCard = document.getElementById('form-progress-card');
            if (progressCard) {
                progressCard.style.display = 'block';
            }
        }

        async function loadSavedPmapc(id) {
            try {
                const res = await fetch(API_BASE + `api/get_pmapc.php?id=${id}`);
                const result = await res.json();
                
                if (result.success && result.exists) {
                    populateForm(result.data);
                    // Cache the loaded data
                    localStorage.setItem(`pmapc_data_${id}`, JSON.stringify(result.data));
                } else {
                    // Reset form to defaults
                    document.getElementById('pmapc-form').reset();
                    document.getElementById('productor_id').value = id;
                    // Clear dynamic rows to 1 default
                    clearDynamicRows();

                    // Re-populate f01_nombre_organizacion since reset() cleared it
                    const prod = allProducers.find(p => p.id == id);
                    if (prod) {
                        const orgInput = document.getElementById('f01_nombre_organizacion');
                        if (orgInput) {
                            orgInput.value = prod.nombre_organizacion || '';
                        }
                    }
                }
                updateFormCompletionProgress();
            } catch (err) {
                console.error("Error loading PMAPC data:", err);
                // Fallback to cache
                const cachedData = localStorage.getItem(`pmapc_data_${id}`);
                if (cachedData) {
                    populateForm(JSON.parse(cachedData));
                    updateFormCompletionProgress();
                    showOfflineNotice("Datos del PMAPC cargados desde la memoria local.");
                } else {
                    // Reset form to defaults
                    document.getElementById('pmapc-form').reset();
                    document.getElementById('productor_id').value = id;
                    clearDynamicRows();
                }
            }
        }

        function populateForm(data) {
            // Helper to set field value if it exists in JSON
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.value = val;
            };

            const setInputByName = (name, val) => {
                const el = document.querySelector(`[name="${name}"]`);
                if (el && val !== undefined) el.value = val;
            };

            // Module 1 (Estratégico)
            if (data.f01) {
                setVal('f01_nombre_organizacion', data.f01.nombre_organizacion);
                setVal('f01_tipo_actividad', data.f01.tipo_actividad);
                setVal('f01_ubicacion', data.f01.ubicacion);
                setVal('f01_coordenadas', data.f01.coordenadas);
                setVal('f01_producto_principal', data.f01.producto_principal);
                setVal('f01_estado_actual', data.f01.estado_actual);
                setVal('f01_descripcion_general', data.f01.descripcion_general);
            }

            if (data.f02) {
                setVal('f02_mision', data.f02.mision);
                setVal('f02_vision', data.f02.vision);
                setVal('f02_valores', data.f02.valores);
            }

            if (data.f03) {
                setVal('f03_problema', data.f03.problema);
                setVal('f03_solucion', data.f03.solucion);
                setVal('f03_diferencial', data.f03.diferencial);
                setVal('f03_valor_ambiental', data.f03.valor_ambiental);
                setVal('f03_valor_social', data.f03.valor_social);
                setVal('f03_demostracion', data.f03.demostracion);
            }

            if (data.f04) {
                setVal('f04_fortalezas', data.f04.fortalezas);
                setVal('f04_oportunidades', data.f04.oportunidades);
                setVal('f04_debilidades', data.f04.debilidades);
                setVal('f04_amenazas', data.f04.amenazas);
            }

            // Module 2 (Mercado)
            const tbl05 = document.getElementById('tbl-f05').getElementsByTagName('tbody')[0];
            tbl05.innerHTML = '';
            if (data.f05 && Array.isArray(data.f05) && data.f05.length > 0) {
                data.f05.forEach(item => {
                    addRowF05(item.actor, item.perfil, item.ubicacion, item.necesidad, item.frecuencia, item.criterio, item.canal);
                });
            } else if (data.f05 && !Array.isArray(data.f05) && Object.keys(data.f05).length > 0) {
                // Backwards compatibility for old saved F05 data
                if (data.f05.perfil_directo) addRowF05('Cliente directo', data.f05.perfil_directo, data.f05.ubicacion_directo, data.f05.necesidad_directo, data.f05.frecuencia_directo, data.f05.criterio_directo, '');
                if (data.f05.perfil_final) addRowF05('Consumidor final', data.f05.perfil_final, data.f05.ubicacion_final, data.f05.necesidad_final, data.f05.frecuencia_final, data.f05.criterio_final, '');
                if (data.f05.perfil_local) addRowF05('Comprador local', data.f05.perfil_local, data.f05.ubicacion_local, data.f05.necesidad_local, data.f05.frecuencia_local, data.f05.criterio_local, '');
                if (data.f05.perfil_inst) addRowF05('Comprador institucional', data.f05.perfil_inst, data.f05.ubicacion_inst, data.f05.necesidad_inst, data.f05.frecuencia_inst, data.f05.criterio_inst, '');
                if (data.f05.perfil_rest) addRowF05('Restaurantes / plazas', data.f05.perfil_rest, data.f05.ubicacion_rest, data.f05.necesidad_rest, data.f05.frecuencia_rest, data.f05.criterio_rest, '');
            } else {
                addRowF05('Cliente directo');
                addRowF05('Comprador local');
                addRowF05('Comprador institucional');
                addRowF05('Consumidor final');
                addRowF05('Aliado comercial');
            }

            if (data.f06) {
                setVal('f06_necesidad', data.f06.necesidad);
                setVal('f06_como_sabe', data.f06.como_sabe);
                setVal('f06_a_quien_afecta', data.f06.a_quien_afecta);
                setVal('f06_evidencia', data.f06.evidencia);
                setVal('f06_oportunidad_organicos', data.f06.oportunidad_organicos);
                setVal('f06_cambio', data.f06.cambio || '');
                setVal('f06_dificultad', data.f06.dificultad || '');
            }

            const tbl07 = document.getElementById('tbl-f07').getElementsByTagName('tbody')[0];
            tbl07.innerHTML = '';
            if (data.f07 && Array.isArray(data.f07) && data.f07.length > 0) {
                data.f07.forEach(item => {
                    addRowF07(item.actor, item.aporta, item.recibe, item.trabajo, item.ambiental, item.accion);
                });
            } else if (data.f07 && !Array.isArray(data.f07) && Object.keys(data.f07).length > 0) {
                // Backwards compatibility for old saved F07 data
                if (data.f07.aporta_vecinos) addRowF07('Productores vecinos', data.f07.aporta_vecinos, data.f07.recibe_vecinos, '', '', data.f07.accion_vecinos);
                if (data.f07.aporta_asoc) addRowF07('Asociación campesina', data.f07.aporta_asoc, data.f07.recibe_asoc, '', '', data.f07.accion_asoc);
                if (data.f07.aporta_jac) addRowF07('Junta de Acción Comunal', data.f07.aporta_jac, data.f07.recibe_jac, '', '', data.f07.accion_jac);
                if (data.f07.aporta_ferias) addRowF07('Ferias o mercados campesinos', data.f07.aporta_ferias, data.f07.recibe_ferias, '', '', data.f07.accion_ferias);
                if (data.f07.aporta_inst) addRowF07('Instituciones de apoyo', data.f07.aporta_inst, data.f07.recibe_inst, '', '', data.f07.accion_inst);
            } else {
                addRowF07('Productores vecinos');
                addRowF07('Asociación campesina');
                addRowF07('Junta de Acción Comunal');
                addRowF07('Compradores locales');
                addRowF07('Ferias o mercados campesinos');
                addRowF07('Instituciones de apoyo');
                addRowF07('Aliados logísticos o digitales');
                addRowF07('Otros aliados');
            }

            if (data.f08) {
                setInputByName('f08_quien_degus', data.f08.quien_degus);
                setInputByName('f08_resultado_degus', data.f08.resultado_degus);
                setInputByName('f08_motivacion_degus', data.f08.motivacion_degus);
                setInputByName('f08_evidencia_degus', data.f08.evidencia_degus);

                setInputByName('f08_quien_ventas', data.f08.quien_ventas);
                setInputByName('f08_resultado_ventas', data.f08.resultado_ventas);
                setInputByName('f08_motivacion_ventas', data.f08.motivacion_ventas);
                setInputByName('f08_evidencia_ventas', data.f08.evidencia_ventas);

                setInputByName('f08_quien_cartas', data.f08.quien_cartas);
                setInputByName('f08_resultado_cartas', data.f08.resultado_cartas);
                setInputByName('f08_motivacion_cartas', data.f08.motivacion_cartas);
                setInputByName('f08_evidencia_cartas', data.f08.evidencia_cartas);

                setInputByName('f08_quien_encuesta', data.f08.quien_encuesta);
                setInputByName('f08_resultado_encuesta', data.f08.resultado_encuesta);
                setInputByName('f08_motivacion_encuesta', data.f08.motivacion_encuesta);
                setInputByName('f08_evidencia_encuesta', data.f08.evidencia_encuesta);

                setInputByName('f08_quien_entrevista', data.f08.quien_entrevista);
                setInputByName('f08_resultado_entrevista', data.f08.resultado_entrevista);
                setInputByName('f08_motivacion_entrevista', data.f08.motivacion_entrevista);
                setInputByName('f08_evidencia_entrevista', data.f08.evidencia_entrevista);

                setInputByName('f08_quien_feria', data.f08.quien_feria);
                setInputByName('f08_resultado_feria', data.f08.resultado_feria);
                setInputByName('f08_motivacion_feria', data.f08.motivacion_feria);
                setInputByName('f08_evidencia_feria', data.f08.evidencia_feria);

                setInputByName('f08_metodo_otro', data.f08.metodo_otro);
                setInputByName('f08_quien_otro', data.f08.quien_otro);
                setInputByName('f08_resultado_otro', data.f08.resultado_otro);
                setInputByName('f08_motivacion_otro', data.f08.motivacion_otro);
                setInputByName('f08_evidencia_otro', data.f08.evidencia_otro);
            }

            // Module 3 (Producción) - Dynamic F09 & F10
            const tbl09 = document.getElementById('tbl-f09').getElementsByTagName('tbody')[0];
            tbl09.innerHTML = '';
            if (data.f09 && data.f09.length > 0) {
                data.f09.forEach(item => {
                    addRowF09(item.producto, item.descripcion, item.unidad, item.insumos, item.almacenamiento, item.presentacion, item.diferencial || '');
                });
            } else {
                addRowF09();
            }

            const tbl10 = document.getElementById('tbl-f10').getElementsByTagName('tbody')[0];
            tbl10.innerHTML = '';
            if (data.f10 && data.f10.length > 0) {
                data.f10.forEach((item) => {
                    addRowF10(item.bien || '', item.unidades || '', item.actividad || '', item.tiempo || '');
                });
            } else {
                addRowF10();
            }

            const tbl11 = document.getElementById('tbl-f11').getElementsByTagName('tbody')[0];
            tbl11.innerHTML = '';
            if (data.f11 && Array.isArray(data.f11) && data.f11.length > 0) {
                data.f11.forEach(item => {
                    addRowF11(item.insumo || '', item.cantidad || '', item.frecuencia || 'Mensual', item.proveedor || '', item.toxicidad || 'N/A', item.impacto || '', item.manejo || '');
                });
            } else if (data.f11 && !Array.isArray(data.f11) && Object.keys(data.f11).length > 0) {
                // Backwards compatibility for old saved F11 data
                if (data.f11.cant_abono || data.f11.prov_abono) addRowF11('Abono / Semillas', data.f11.cant_abono, data.f11.frec_abono || 'Mensual', data.f11.prov_abono, 'N/A', '', data.f11.sost_abono || '');
                if (data.f11.cant_agua || data.f11.prov_agua) addRowF11('Agua de riego', data.f11.cant_agua, data.f11.frec_agua || 'Mensual', data.f11.prov_agua, 'N/A', '', data.f11.sost_agua || '');
                if (data.f11.cant_emp || data.f11.prov_emp) addRowF11('Empaques', data.f11.cant_emp, data.f11.frec_emp || 'Mensual', data.f11.prov_emp, 'N/A', '', data.f11.sost_emp || '');
            } else {
                addRowF11();
            }

            if (data.f12) {
                setVal('f12_produccion_estimada', data.f12.produccion_estimada);
                setVal('f12_produccion_maxima', data.f12.produccion_maxima);
                setVal('f12_area', data.f12.area || '');
                setVal('f12_limitantes_prod', data.f12.limitantes_prod);
                setVal('f12_limitantes_amb', data.f12.limitantes_amb);
                setVal('f12_capacidad_instalada', data.f12.capacidad_instalada);
                setVal('f12_capacidad_utilizada', data.f12.capacidad_utilizada);
                setVal('f12_misma_cantidad', data.f12.misma_cantidad || '');
                setVal('f12_alcanza_demanda', data.f12.alcanza_demanda || '');
                setVal('f12_necesidad_sostenible', data.f12.necesidad_sostenible || '');
            }

            // Module 4 (Límites)
            if (data.f12a) {
                setInputByName('f12a_estado_agua', data.f12a.estado_agua);
                setInputByName('f12a_limite_agua', data.f12a.limite_agua);
                setInputByName('f12a_efecto_agua', data.f12a.efecto_agua);
                setInputByName('f12a_accion_agua', data.f12a.accion_agua);

                setInputByName('f12a_estado_fuentes', data.f12a.estado_fuentes);
                setInputByName('f12a_limite_fuentes', data.f12a.limite_fuentes);
                setInputByName('f12a_efecto_fuentes', data.f12a.efecto_fuentes);
                setInputByName('f12a_accion_fuentes', data.f12a.accion_fuentes);

                setInputByName('f12a_estado_suelo', data.f12a.estado_suelo);
                setInputByName('f12a_limite_suelo', data.f12a.limite_suelo);
                setInputByName('f12a_efecto_suelo', data.f12a.efecto_suelo);
                setInputByName('f12a_accion_suelo', data.f12a.accion_suelo);

                setInputByName('f12a_estado_pendiente', data.f12a.estado_pendiente);
                setInputByName('f12a_limite_pendiente', data.f12a.limite_pendiente);
                setInputByName('f12a_efecto_pendiente', data.f12a.efecto_pendiente);
                setInputByName('f12a_accion_pendiente', data.f12a.accion_pendiente);

                setInputByName('f12a_estado_clima', data.f12a.estado_clima);
                setInputByName('f12a_limite_clima', data.f12a.limite_clima);
                setInputByName('f12a_efecto_clima', data.f12a.efecto_clima);
                setInputByName('f12a_accion_clima', data.f12a.accion_clima);

                setInputByName('f12a_estado_bio', data.f12a.estado_bio);
                setInputByName('f12a_limite_bio', data.f12a.limite_bio);
                setInputByName('f12a_efecto_bio', data.f12a.efecto_bio);
                setInputByName('f12a_accion_bio', data.f12a.accion_bio);

                setInputByName('f12a_estado_insumos', data.f12a.estado_insumos);
                setInputByName('f12a_limite_insumos', data.f12a.limite_insumos);
                setInputByName('f12a_efecto_insumos', data.f12a.efecto_insumos);
                setInputByName('f12a_accion_insumos', data.f12a.accion_insumos);

                setInputByName('f12a_estado_residuos', data.f12a.estado_residuos);
                setInputByName('f12a_limite_residuos', data.f12a.limite_residuos);
                setInputByName('f12a_efecto_residuos', data.f12a.efecto_residuos);
                setInputByName('f12a_accion_residuos', data.f12a.accion_residuos);
            }

            if (data.f12b) {
                const keys = ['virus', 'bacterias', 'picaduras', 'mordeduras', 'temperatura', 'radiacion', 'ruido', 'polvos', 'gases', 'particulado', 'posturas', 'movimientos', 'cargas', 'mecanico', 'locativo', 'electrico', 'transito'];
                keys.forEach(k => {
                    const row = data.f12b[k];
                    if (row) {
                        const siBox = document.querySelector(`[name="f12b_${k}_si"]`);
                        if (siBox) siBox.checked = row.si;
                        const noBox = document.querySelector(`[name="f12b_${k}_no"]`);
                        if (noBox) noBox.checked = row.no;
                        const altaBox = document.querySelector(`[name="f12b_${k}_f_alta"]`);
                        if (altaBox) altaBox.checked = row.f_alta;
                        const mediaBox = document.querySelector(`[name="f12b_${k}_f_media"]`);
                        if (mediaBox) mediaBox.checked = row.f_media;
                        const bajaBox = document.querySelector(`[name="f12b_${k}_f_baja"]`);
                        if (bajaBox) bajaBox.checked = row.f_baja;
                        
                        setInputByName(`f12b_${k}_controles`, row.controles);
                        setInputByName(`f12b_${k}_mejora`, row.mejora);
                    }
                });
            }

            if (data.f12c) {
                for (let i = 1; i <= 7; i++) {
                    const row = data.f12c[i];
                    if (row) {
                        setInputByName(`f12c_resp_${i}`, row.resp);
                        setInputByName(`f12c_frec_${i}`, row.frec);
                        setInputByName(`f12c_evidencia_${i}`, row.evidencia);
                    }
                }
            }

                        // Module 5 (Comercial)
            if (data.f13) {
                for (let i = 0; i <= 7; i++) {
                    const row = data.f13[i];
                    if (row) {
                        setInputByName(`f13_aplica_${i}`, row.aplica);
                        setInputByName(`f13_detalles_${i}`, row.detalles);
                        setInputByName(`f13_frec_${i}`, row.frec);
                        setInputByName(`f13_resp_${i}`, row.resp);
                    }
                }
            }

            // Dynamic F14 (Precios)
            const tbl14 = document.getElementById('tbl-f14').getElementsByTagName('tbody')[0];
            tbl14.innerHTML = '';
            if (data.f14 && data.f14.length > 0) {
                data.f14.forEach(item => {
                    addRowF14(item.producto, item.costo, item.margen, item.pmin, item.pmercado, item.logistica, item.precio, item.justificacion);
                });
            } else {
                addRowF14();
            }

            // Dynamic F15 (Ventas)
            const tbl15 = document.getElementById('tbl-f15').getElementsByTagName('tbody')[0];
            tbl15.innerHTML = '';
            if (data.f15 && data.f15.length > 0) {
                data.f15.forEach(item => {
                    addRowF15(item.producto, item.cantidad, item.precio, item.ingresos, item.pago, item.cliente);
                });
            } else {
                addRowF15();
            }

            // F15A Fidelización
            if (data.f15a) {
                for (let i = 0; i <= 4; i++) {
                    const row = data.f15a[i];
                    if (row) {
                        setInputByName(`f15a_est_${i}`, row.est);
                        setInputByName(`f15a_med_${i}`, row.med);
                        setInputByName(`f15a_frec_${i}`, row.frec);
                        setInputByName(`f15a_resp_${i}`, row.resp);
                    }
                }
            }

            // Dynamic F15B Logística
            const tbl15b = document.getElementById('tbl-f15b').getElementsByTagName('tbody')[0];
            tbl15b.innerHTML = '';
            if (data.f15b && data.f15b.length > 0) {
                data.f15b.forEach(item => {
                    addRowF15B(item.prod, item.canal, item.tiempo, item.transporte, item.condicion, item.capacidad, item.costo, item.resp);
                });
            } else {
                addRowF15B();
            }

            // F15C Trazabilidad
            if (data.f15c) {
                for (let i = 0; i <= 4; i++) {
                    const row = data.f15c[i];
                    if (row) {
                        setInputByName(`f15c_resp_${i}`, row.resp);
                        setInputByName(`f15c_med_${i}`, row.med);
                        setInputByName(`f15c_frec_${i}`, row.frec);
                        setInputByName(`f15c_evi_${i}`, row.evi);
                    }
                }
            }

                        // Module 6 (Finanzas)
            if (data.f16) {
                for (let i = 0; i <= 8; i++) {
                    const row = data.f16[i];
                    if (row) {
                        setInputByName(`f16_desc_${i}`, row.desc);
                        setInputByName(`f16_valunit_${i}`, row.valunit);
                        setInputByName(`f16_cant_${i}`, row.cant);
                        setInputByName(`f16_req_${i}`, row.req);
                        setInputByName(`f16_fuente_${i}`, row.fuente);
                        calcInv(i);
                    }
                }
            }

            if (data.f17) {
                for (let i = 0; i <= 5; i++) {
                    const row = data.f17[i];
                    if (row) {
                        setInputByName(`f17_desc_${i}`, row.desc);
                        setInputByName(`f17_val_${i}`, row.val);
                        setInputByName(`f17_obs_${i}`, row.obs);
                    }
                }
            }

            if (data.f18) {
                for (let i = 1; i <= 6; i++) {
                    setInputByName(`f18_ingreso_m${i}`, data.f18[`ingreso_m${i}`]);
                    setInputByName(`f18_gprod_m${i}`, data.f18[`gprod_m${i}`]);
                    setInputByName(`f18_gamb_m${i}`, data.f18[`gamb_m${i}`]);
                    setInputByName(`f18_glog_m${i}`, data.f18[`glog_m${i}`]);
                    setInputByName(`f18_obs_m${i}`, data.f18[`obs_m${i}`]);
                    calcNeto(i);
                }
            }

                        // Module 7 (Sostenibilidad)
            if (data.f19_conclusion) setVal('f19_conclusion', data.f19_conclusion);
            if (data.f19) {
                for (let i = 0; i <= 6; i++) {
                    const row = data.f19[i];
                    if (row) {
                        setInputByName(`f19_ini_${i}`, row.ini);
                        setInputByName(`f19_meta_${i}`, row.meta);
                        setInputByName(`f19_frec_${i}`, row.frec);
                        setInputByName(`f19_resp_${i}`, row.resp);
                        setInputByName(`f19_evi_${i}`, row.evi);
                    }
                }
            }

            if (data.f19a_conclusion) setVal('f19a_conclusion', data.f19a_conclusion);
            if (data.f19a) {
                for (let i = 0; i <= 13; i++) {
                    const row = data.f19a[i];
                    if (row) {
                        setInputByName(`f19a_desc_${i}`, row.desc);
                        setInputByName(`f19a_cant_${i}`, row.cant);
                        setInputByName(`f19a_impacto_${i}`, row.impacto);
                        setInputByName(`f19a_mejora_${i}`, row.mejora);
                    }
                }
                calcTotalF19A();
            }

            if (data.f20_conclusion) setVal('f20_conclusion', data.f20_conclusion);
            if (data.f20) {
                for (let i = 0; i <= 4; i++) {
                    const row = data.f20[i];
                    if (row) {
                        setInputByName(`f20_cant_${i}`, row.cant);
                        setInputByName(`f20_manejo_${i}`, row.manejo);
                        setInputByName(`f20_destino_${i}`, row.destino);
                        setInputByName(`f20_resp_${i}`, row.resp);
                    }
                }
                calcTotalF20();
            }

            if (data.f21_conclusion) setVal('f21_conclusion', data.f21_conclusion);
            if (data.f21) {
                for (let i = 0; i <= 8; i++) {
                    const row = data.f21[i];
                    if (row) {
                        setInputByName(`f21_estado_${i}`, row.estado);
                        setInputByName(`f21_cal_${i}`, row.cal);
                        setInputByName(`f21_mejora_${i}`, row.mejora);
                        setInputByName(`f21_evi_${i}`, row.evi);
                    }
                }
                calcTotalF21();
            }

            if (data.f22) {
                for (let i = 0; i <= 6; i++) {
                    const row = data.f22[i];
                    if (row) {
                        setInputByName(`f22_accion_${i}`, row.accion);
                        setInputByName(`f22_plazo_${i}`, row.plazo);
                        setInputByName(`f22_resp_${i}`, row.resp);
                        setInputByName(`f22_rec_${i}`, row.rec);
                        setInputByName(`f22_ind_${i}`, row.ind);
                        setInputByName(`f22_evi_${i}`, row.evi);
                    }
                }
            }

            if (data.f22a_conclusion) setVal('f22a_conclusion', data.f22a_conclusion);
            if (data.f22a) {
                for (let i = 0; i <= 5; i++) {
                    const row = data.f22a[i];
                    if (row) {
                        setInputByName(`f22a_resp_${i}`, row.resp);
                        setInputByName(`f22a_riesgo_${i}`, row.riesgo);
                        setInputByName(`f22a_mejora_${i}`, row.mejora);
                    }
                }
            }

            // Module 8 (Riesgos)
            if (data.f23) {
                setInputByName('f23_causa_clima', data.f23.causa_clima);
                setInputByName('f23_cons_clima', data.f23.cons_clima);
                setInputByName('f23_nivel_clima', data.f23.nivel_clima);
                setInputByName('f23_prev_clima', data.f23.prev_clima);

                setInputByName('f23_causa_costos', data.f23.causa_costos);
                setInputByName('f23_cons_costos', data.f23.cons_costos);
                setInputByName('f23_nivel_costos', data.f23.nivel_costos);
                setInputByName('f23_prev_costos', data.f23.prev_costos);
            }

            // Dynamic F24 (Plan Acción)
            const tbl24 = document.getElementById('tbl-f24').getElementsByTagName('tbody')[0];
            tbl24.innerHTML = '';
            if (data.f24 && data.f24.length > 0) {
                data.f24.forEach(item => {
                    addRowF24(item.actividad, item.componente, item.responsable, item.tiempo, item.resultado);
                });
            } else {
                addRowF24();
            }

            if (data.f25) {
                for (let i = 0; i <= 8; i++) {
                    const row = data.f25[i];
                    if (row) {
                        if (row.ind) setInputByName(`f25_ind_${i}`, row.ind);
                        setInputByName(`f25_meta_${i}`, row.meta);
                        setInputByName(`f25_frec_${i}`, row.frec);
                        setInputByName(`f25_resp_${i}`, row.resp);
                        setInputByName(`f25_evi_${i}`, row.evi);
                    }
                }
            }

            if (data.f26) {
                for (let i = 0; i <= 8; i++) {
                    const row = data.f26[i];
                    if (row) {
                        setInputByName(`f26_prod_${i}`, row.prod);
                        setInputByName(`f26_com_${i}`, row.com);
                        setInputByName(`f26_fin_${i}`, row.fin);
                        setInputByName(`f26_amb_${i}`, row.amb);
                        setInputByName(`f26_aju_${i}`, row.aju);
                    }
                }
            }

            if (data.f26_coherencia) setVal('f26_coherencia', data.f26_coherencia);
            updateFormCompletionProgress();
        }

        // Form Completion Progress Bar
        function updateFormCompletionProgress() {
            if (!selectedProducerId) {
                const progressBar = document.getElementById('form-progress-bar');
                const progressText = document.getElementById('form-progress-text');
                if (progressBar) progressBar.style.width = '0%';
                if (progressText) progressText.innerText = '0% completado';
                return;
            }

            const form = document.getElementById('pmapc-form');
            if (!form) return;

            // 1. Gather all inputs inside the form
            const allInputs = Array.from(form.querySelectorAll('input, textarea, select'));
            
            // 2. Gather edit fields from info box
            const editFields = [
                document.getElementById('edit-vereda'),
                document.getElementById('edit-predio'),
                document.getElementById('edit-fecha-nacimiento'),
                document.getElementById('edit-telefono'),
                document.getElementById('edit-correo')
            ];

            let totalFields = 0;
            let filledFields = 0;

            const checkAndCount = (el) => {
                if (!el) return;
                
                // Skip hidden inputs
                if (el.tagName === 'INPUT' && el.type === 'hidden') return;
                
                // Skip submit or button type inputs
                if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button')) return;

                totalFields++;
                
                // Check if filled
                if (el.tagName === 'SELECT') {
                    if (el.value !== '') {
                        filledFields++;
                    }
                } else if (el.type === 'checkbox') {
                    if (el.checked) {
                        filledFields++;
                    }
                } else if (el.type === 'radio') {
                    // Handled just in case, though there are none
                } else {
                    if (el.value && el.value.trim() !== '') {
                        filledFields++;
                    }
                }
            };

            allInputs.forEach(checkAndCount);
            editFields.forEach(checkAndCount);

            const percentage = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;

            const progressBar = document.getElementById('form-progress-bar');
            const progressText = document.getElementById('form-progress-text');

            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
            if (progressText) {
                progressText.innerText = percentage + '% completado';
            }
        }

        function clearDynamicRows() {
            document.getElementById('tbl-f05').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f07').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f09').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f10').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f14').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f23').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f24').getElementsByTagName('tbody')[0].innerHTML = '';

            addRowF05('Cliente directo');
            addRowF05('Consumidor final');
            addRowF05('Comprador local');
            addRowF05('Comprador institucional');
            addRowF05('Restaurantes / tiendas / plazas');
            addRowF05('Otro');

            addRowF07('Productores vecinos');
            addRowF07('Asociación campesina');
            addRowF07('Junta de Acción Comunal');
            addRowF07('Compradores locales');
            addRowF07('Ferias o mercados campesinos');
            addRowF07('Instituciones de apoyo');
            addRowF07('Aliados logísticos o digitales');
            addRowF07('Otros aliados');

            addRowF09();
            addRowF10();
            addRowF14();
            addRowF23();
            addRowF24();
        }

        // Stepper Navigation
        function goToStep(step) {
            // Se elimina la validación obligatoria de selectedProducerId para permitir navegación libre
            if (step < 1 || step > totalSteps) return;
            
            // Hide current section
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            
            // Update node classes
            const nodes = document.querySelectorAll('.step-node');
            nodes[currentStep - 1].classList.remove('active');
            
            // Add completed classes
            for (let i = 0; i < step - 1; i++) {
                nodes[i].classList.add('completed');
            }
            for (let i = step - 1; i < totalSteps; i++) {
                nodes[i].classList.remove('completed');
            }

            currentStep = step;
            
            // Show new section
            document.getElementById(`step-${currentStep}`).classList.add('active');
            nodes[currentStep - 1].classList.add('active');

            // Scroll to top of form
            window.scrollTo({ top: document.querySelector('.stepper-container').offsetTop - 90, behavior: 'smooth' });

            // Button configurations
            document.getElementById('btn-prev').style.display = currentStep === 1 ? 'none' : 'block';
            
            const nextBtn = document.getElementById('btn-next');
            if (currentStep === totalSteps) {
                nextBtn.textContent = "Guardar Plan de Manejo";
                nextBtn.style.backgroundColor = "var(--primary-green)";
            } else {
                nextBtn.textContent = "Siguiente →";
                nextBtn.style.backgroundColor = "";
            }

            // Stepper Line Progress
            const progress = ((step - 1) / (totalSteps - 1)) * 90;
            document.getElementById('stepper-progress').style.width = `${progress}%`;
        }

        function changeStep(direction) {
            const nextStep = currentStep + direction;
            if (nextStep === totalSteps + 1) {
                // Submit Form
                submitForm();
            } else {
                goToStep(nextStep);
            }
        }

        // Add/Remove Rows logic
        function removeRow(btn) {
            const row = btn.closest('tr');
            const tbody = row.parentNode;
            if (tbody.rows.length > 1) {
                row.parentNode.removeChild(row);
                // Reindex steps for F10
                if (tbody.parentNode.id === 'tbl-f10') {
                    reindexSteps();
                }
                updateFormCompletionProgress();
            } else {
                alert("Debe mantener al menos una fila en el formulario.");
            }
        }

        function addRowF05(actor='', perfil='', ubicacion='', necesidad='', frecuencia='', criterio='', canal='') {
            const tbody = document.getElementById('tbl-f05').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f05";
            
            // Actor options
            const actorOpts = [
                "Cliente directo",
                "Consumidor final",
                "Comprador local",
                "Comprador institucional",
                "Restaurantes / tiendas / plazas",
                "Otro"
            ];
            let actorHtml = '';
            let actorMatched = false;
            actorOpts.forEach(opt => {
                const isSel = (actor === opt) ? 'selected' : '';
                if (isSel) actorMatched = true;
                actorHtml += `<option value="${opt}" ${isSel}>${opt}</option>`;
            });
            if (actor && !actorMatched) {
                actorHtml += `<option value="${actor}" selected>${actor}</option>`;
            }

            // Frecuencia options
            const frecOpts = [
                "",
                "Diario",
                "Semanal",
                "Quincenal",
                "Mensual",
                "Ocasional",
                "En cosecha"
            ];
            let frecHtml = '';
            let frecMatched = false;
            frecOpts.forEach(opt => {
                const isSel = (frecuencia.toLowerCase() === opt.toLowerCase() || frecuencia === opt) ? 'selected' : '';
                if (isSel) frecMatched = true;
                const label = opt === "" ? "Seleccione..." : opt;
                frecHtml += `<option value="${opt}" ${isSel}>${label}</option>`;
            });
            if (frecuencia && !frecMatched) {
                frecHtml += `<option value="${frecuencia}" selected>${frecuencia}</option>`;
            }

            // Criterio options
            const critOpts = [
                "",
                "Precio",
                "Calidad",
                "Tamaño",
                "Presentación",
                "Producción limpia",
                "Confianza",
                "Cercanía",
                "Disponibilidad",
                "Certificación",
                "Otro"
            ];
            let critHtml = '';
            let critMatched = false;
            critOpts.forEach(opt => {
                const isSel = (criterio.toLowerCase() === opt.toLowerCase() || criterio === opt) ? 'selected' : '';
                if (isSel) critMatched = true;
                const label = opt === "" ? "Seleccione..." : opt;
                critHtml += `<option value="${opt}" ${isSel}>${label}</option>`;
            });
            if (criterio && !critMatched) {
                critHtml += `<option value="${criterio}" selected>${criterio}</option>`;
            }

            // Canal options
            const canalOpts = [
                "",
                "En finca",
                "Mercado campesino",
                "Plaza de mercado",
                "Asociación",
                "Teléfono",
                "WhatsApp",
                "Redes sociales",
                "Web",
                "Otro"
            ];
            let canalHtml = '';
            let canalMatched = false;
            canalOpts.forEach(opt => {
                const isSel = (canal.toLowerCase() === opt.toLowerCase() || canal === opt) ? 'selected' : '';
                if (isSel) canalMatched = true;
                const label = opt === "" ? "Seleccione..." : opt;
                canalHtml += `<option value="${opt}" ${isSel}>${label}</option>`;
            });
            if (canal && !canalMatched) {
                canalHtml += `<option value="${canal}" selected>${canal}</option>`;
            }

            tr.innerHTML = `
                <td>
                    <select name="f05_actor[]" style="width: 100%;">
                        ${actorHtml}
                    </select>
                </td>
                <td><input type="text" name="f05_perfil[]" value="${perfil}" placeholder="Ej. Familias, buscan papa nativa, 5 kg"></td>
                <td><input type="text" name="f05_ubicacion[]" value="${ubicacion}" placeholder="Ej. Bogotá"></td>
                <td><input type="text" name="f05_necesidad[]" value="${necesidad}" placeholder="Ej. Alimentación sana, valora origen limpio"></td>
                <td>
                    <select name="f05_frecuencia[]" style="width: 100%;">
                        ${frecHtml}
                    </select>
                </td>
                <td>
                    <select name="f05_criterio[]" style="width: 100%;">
                        ${critHtml}
                    </select>
                </td>
                <td>
                    <select name="f05_canal[]" style="width: 100%;">
                        ${canalHtml}
                    </select>
                </td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF07(actor='', aporta='', recibe='', trabajo='', ambiental='', accion='') {
            const tbody = document.getElementById('tbl-f07').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f07";
            tr.innerHTML = `
                <td><input type="text" name="f07_actor[]" value="${actor}" placeholder="Ej. Vecinos"></td>
                <td><input type="text" name="f07_aporta[]" value="${aporta}" placeholder="Ej. Flete"></td>
                <td><input type="text" name="f07_recibe[]" value="${recibe}" placeholder="Ej. Apoyo"></td>
                <td><input type="text" name="f07_trabajo[]" value="${trabajo}" placeholder="Ej. Compartir gastos"></td>
                <td><input type="text" name="f07_ambiental[]" value="${ambiental}" placeholder="Ej. Reciclaje"></td>
                <td><input type="text" name="f07_accion[]" value="${accion}" placeholder="Ej. Coordinar salidas"></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF09(prod='', desc='', unit='', ins='', storage='', pres='', dif='') {
            const tbody = document.getElementById('tbl-f09').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f09";
            tr.innerHTML = `
                <td><input type="text" name="f09_producto[]" value="${prod}" placeholder="Ej. Queso Fresco"></td>
                <td><input type="text" name="f09_descripcion[]" value="${desc}"></td>
                <td><input type="text" name="f09_unidad[]" value="${unit}" placeholder="Ej. kg"></td>
                <td><input type="text" name="f09_insumos[]" value="${ins}"></td>
                <td><input type="text" name="f09_almacenamiento[]" value="${storage}"></td>
                <td><input type="text" name="f09_presentacion[]" value="${pres}"></td>
                <td><input type="text" name="f09_diferencial[]" value="${dif}"></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF10(bien='', unidades='', act='', time='') {
            const tbody = document.getElementById('tbl-f10').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f10";
            tr.innerHTML = `
                <td><input type="text" name="f10_bien[]" value="${bien}" placeholder="Ej. Queso Fresco"></td>
                <td><input type="number" name="f10_unidades[]" value="${unidades}" placeholder="Ej. 100"></td>
                <td><input type="text" name="f10_actividad[]" value="${act}" placeholder="Ej. Ordeño y filtrado"></td>
                <td><input type="text" name="f10_tiempo[]" value="${time}" placeholder="Ej. 1 hora"></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF11(insumo='', cantidad='', frecuencia='Mensual', proveedor='', toxicidad='N/A', impacto='', manejo='') {
            const tbody = document.getElementById('tbl-f11').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f11";
            tr.innerHTML = `
                <td><input type="text" name="f11_insumo[]" value="${insumo}" placeholder="Ej. Fertilizante químico"></td>
                <td><input type="text" name="f11_cantidad[]" value="${cantidad}" placeholder="Ej. 10 kg"></td>
                <td>
                    <select name="f11_frecuencia[]">
                        <option value="Mensual" ${frecuencia === 'Mensual' ? 'selected' : ''}>Mensual</option>
                        <option value="Trimestral" ${frecuencia === 'Trimestral' ? 'selected' : ''}>Trimestral</option>
                        <option value="Ocasional" ${frecuencia === 'Ocasional' ? 'selected' : ''}>Ocasional</option>
                        <option value="Permanente" ${frecuencia === 'Permanente' ? 'selected' : ''}>Permanente</option>
                    </select>
                </td>
                <td><input type="text" name="f11_proveedor[]" value="${proveedor}"></td>
                <td>
                    <select name="f11_toxicidad[]">
                        <option value="Franja roja" ${toxicidad === 'Franja roja' ? 'selected' : ''}>Franja roja</option>
                        <option value="Franja amarilla" ${toxicidad === 'Franja amarilla' ? 'selected' : ''}>Franja amarilla</option>
                        <option value="Franja azul" ${toxicidad === 'Franja azul' ? 'selected' : ''}>Franja azul</option>
                        <option value="Franja verde" ${toxicidad === 'Franja verde' ? 'selected' : ''}>Franja verde</option>
                        <option value="N/A" ${toxicidad === 'N/A' ? 'selected' : ''}>N/A</option>
                    </select>
                </td>
                <td><input type="text" name="f11_impacto[]" value="${impacto}"></td>
                <td><input type="text" name="f11_manejo[]" value="${manejo}"></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

                function addRowF14(prod='', cost='', margin='', pmin='', pmercado='', log='', price='', just='') {
            const tbody = document.getElementById('tbl-f14').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f14";
            tr.innerHTML = `
                <td><input type="text" name="f14_producto[]" value="${prod}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f14_costo[]" value="${cost}" oninput="calcPrecio(this)"></td>
                <td><input type="number" step="0.1" name="f14_margen[]" value="${margin}" oninput="calcPrecio(this)"></td>
                <td><input type="number" step="0.01" name="f14_pmin[]" value="${pmin}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f14_pmercado[]" value="${pmercado}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f14_logistica[]" value="${log}" oninput="calcPrecio(this)"></td>
                <td><input type="number" name="f14_precio[]" value="${price || ''}" readonly></td>
                <td><input type="text" name="f14_justificacion[]" value="${just}"></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF15(prod='', cant='', precio='', ingresos='', pago='', cliente='') {
            const tbody = document.getElementById('tbl-f15').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f15";
            tr.innerHTML = `
                <td><input type="text" name="f15_producto[]" value="${prod}" placeholder="..."></td>
                <td><input type="number" name="f15_cantidad[]" value="${cant}" oninput="calcIngresos(this)"></td>
                <td><input type="number" name="f15_precio[]" value="${precio}" oninput="calcIngresos(this)"></td>
                <td><input type="number" name="f15_ingresos[]" value="${ingresos || ''}" readonly></td>
                <td><input type="text" name="f15_pago[]" value="${pago}" placeholder="..."></td>
                <td><input type="text" name="f15_cliente[]" value="${cliente}" placeholder="..."></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF15B(prod='', canal='', tiempo='', transporte='', condicion='', capacidad='', costo='', resp='') {
            const tbody = document.getElementById('tbl-f15b').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f15b";
            tr.innerHTML = `
                <td><input type="text" name="f15b_prod[]" value="${prod}" placeholder="..."></td>
                <td><input type="text" name="f15b_canal[]" value="${canal}" placeholder="..."></td>
                <td><input type="text" name="f15b_tiempo[]" value="${tiempo}" placeholder="..."></td>
                <td><input type="text" name="f15b_transporte[]" value="${transporte}" placeholder="..."></td>
                <td><input type="text" name="f15b_condicion[]" value="${condicion}" placeholder="..."></td>
                <td><input type="text" name="f15b_capacidad[]" value="${capacidad}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f15b_costo[]" value="${costo}" placeholder="..."></td>
                <td><input type="text" name="f15b_resp[]" value="${resp}" placeholder="..."></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        

        function addRowF23(tipo='Comercial (Baja demanda)', riesgo='', causa='', consecuencia='', nivel='Medio', prev='', resp='') {
            const tbody = document.getElementById('tbl-f23').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f23";
            
            const options = [
                "Comercial (Baja demanda)",
                "Productivo (Baja producción)",
                "Ambiental (Afectación de fuentes hídricas)",
                "Ambiental (Deterioro del suelo)",
                "Climático (Heladas, lluvias intensas o sequías)",
                "Logístico (Pérdida de frescura o retraso en entregas)",
                "Digital (Falta de actualización del QR, redes o base de datos)",
                "Organizativo (Falta de cooperación entre productores)",
                "Financiero (Aumento de costos)"
            ];
            
            let optionsHtml = '';
            let matched = false;
            options.forEach(opt => {
                const isSel = (tipo === opt) ? 'selected' : '';
                if (isSel) matched = true;
                optionsHtml += `<option value="${opt}" ${isSel}>${opt}</option>`;
            });
            
            if (tipo && !matched) {
                optionsHtml += `<option value="${tipo}" selected>${tipo}</option>`;
            }

            tr.innerHTML = `
                <td>
                    <select name="f23_tipo[]" onchange="handleF23TypeChange(this)" style="width: 100%;">
                        ${optionsHtml}
                    </select>
                </td>
                <td><input type="text" name="f23_riesgo[]" value="${riesgo}" placeholder="Ej. Baja demanda"></td>
                <td><input type="text" name="f23_causa[]" value="${causa}" placeholder="..."></td>
                <td><input type="text" name="f23_consecuencia[]" value="${consecuencia}" placeholder="..."></td>
                <td>
                    <select name="f23_nivel[]">
                        <option value="Alto" ${nivel === 'Alto' ? 'selected' : ''}>Alto</option>
                        <option value="Medio" ${nivel === 'Medio' ? 'selected' : ''}>Medio</option>
                        <option value="Bajo" ${nivel === 'Bajo' ? 'selected' : ''}>Bajo</option>
                    </select>
                </td>
                <td><input type="text" name="f23_prevencion[]" value="${prev}" placeholder="..."></td>
                <td><input type="text" name="f23_respuesta[]" value="${resp}" placeholder="..."></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function handleF23TypeChange(selectEl) {
            const tr = selectEl.closest('tr');
            const riesgoInput = tr.querySelector('[name="f23_riesgo[]"]');
            if (riesgoInput && !riesgoInput.value) {
                const val = selectEl.value;
                const match = val.match(/\(([^)]+)\)/);
                if (match && match[1]) {
                    riesgoInput.value = match[1];
                }
            }
        }

        function addRowF24(act='', comp='Digital', resp='', time='', res='') {
            const tbody = document.getElementById('tbl-f24').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f24";
            tr.innerHTML = `
                <td><input type="text" name="f24_actividad[]" value="${act}" placeholder="Ej. Etiquetas con QR"></td>
                <td>
                    <select name="f24_componente[]">
                        <option value="Digital" ${comp === 'Digital' ? 'selected' : ''}>Digital / Trazabilidad</option>
                        <option value="Productivo" ${comp === 'Productivo' ? 'selected' : ''}>Productivo</option>
                        <option value="Comercial" ${comp === 'Comercial' ? 'selected' : ''}>Comercial</option>
                        <option value="Financiero" ${comp === 'Financiero' ? 'selected' : ''}>Financiero</option>
                        <option value="Ambiental" ${comp === 'Ambiental' ? 'selected' : ''}>Ambiental</option>
                        <option value="Cooperacion" ${comp === 'Cooperacion' ? 'selected' : ''}>Cooperación Territorial</option>
                    </select>
                </td>
                <td><input type="text" name="f24_responsable[]" value="${resp}"></td>
                <td><input type="text" name="f24_tiempo[]" value="${time}" placeholder="Ej. Mes 2"></td>
                <td><input type="text" name="f24_resultado[]" value="${res}"></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

                function getTableDataF19() {
            const arr = [];
            for (let i = 0; i <= 6; i++) {
                arr.push({
                    ini: document.querySelector(`[name="f19_ini_${i}"]`)?.value || '',
                    meta: document.querySelector(`[name="f19_meta_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f19_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f19_resp_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f19_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF19A() {
            const arr = [];
            for (let i = 0; i <= 13; i++) {
                arr.push({
                    desc: document.querySelector(`[name="f19a_desc_${i}"]`)?.value || '',
                    cant: document.querySelector(`[name="f19a_cant_${i}"]`)?.value || '',
                    impacto: document.querySelector(`[name="f19a_impacto_${i}"]`)?.value || '',
                    mejora: document.querySelector(`[name="f19a_mejora_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF20() {
            const arr = [];
            for (let i = 0; i <= 4; i++) {
                arr.push({
                    cant: document.querySelector(`[name="f20_cant_${i}"]`)?.value || '',
                    manejo: document.querySelector(`[name="f20_manejo_${i}"]`)?.value || '',
                    destino: document.querySelector(`[name="f20_destino_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f20_resp_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF21() {
            const arr = [];
            for (let i = 0; i <= 8; i++) {
                arr.push({
                    estado: document.querySelector(`[name="f21_estado_${i}"]`)?.value || '',
                    cal: document.querySelector(`[name="f21_cal_${i}"]`)?.value || '',
                    mejora: document.querySelector(`[name="f21_mejora_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f21_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF22() {
            const arr = [];
            for (let i = 0; i <= 6; i++) {
                arr.push({
                    accion: document.querySelector(`[name="f22_accion_${i}"]`)?.value || '',
                    plazo: document.querySelector(`[name="f22_plazo_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f22_resp_${i}"]`)?.value || '',
                    rec: document.querySelector(`[name="f22_rec_${i}"]`)?.value || '',
                    ind: document.querySelector(`[name="f22_ind_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f22_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF22A() {
            const arr = [];
            for (let i = 0; i <= 5; i++) {
                arr.push({
                    resp: document.querySelector(`[name="f22a_resp_${i}"]`)?.value || '',
                    riesgo: document.querySelector(`[name="f22a_riesgo_${i}"]`)?.value || 'Bajo',
                    mejora: document.querySelector(`[name="f22a_mejora_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function calcTotalF19A() {
            let sum = 0;
            for (let i = 0; i <= 13; i++) {
                const val = parseFloat(document.querySelector(`[name="f19a_impacto_${i}"]`)?.value) || 0;
                sum += val;
            }
            document.getElementById('f19a_total_impacto').value = sum > 0 ? sum : '';
        }

        function calcTotalF20() {
            let sum = 0;
            for (let i = 0; i <= 4; i++) {
                const val = parseFloat(document.querySelector(`[name="f20_cant_${i}"]`)?.value) || 0;
                sum += val;
            }
            document.getElementById('f20_total_cant').value = sum > 0 ? sum : '';
        }

        function calcTotalF21() {
            let sum = 0;
            for (let i = 0; i <= 8; i++) {
                const val = parseFloat(document.querySelector(`[name="f21_cal_${i}"]`)?.value) || 0;
                sum += val;
            }
            document.getElementById('f21_total_cal').value = sum > 0 ? sum : '';
        }

        // Calculations helper
        function calcPrecio(input) {
            const row = input.closest('tr');
            const costo = parseFloat(row.querySelector('[name="f14_costo[]"]').value) || 0;
            const margen = parseFloat(row.querySelector('[name="f14_margen[]"]').value) || 0;
            const logistica = parseFloat(row.querySelector('[name="f14_logistica[]"]').value) || 0;

            // Price = Costo + (Costo * Margen/100) + Costo Logístico
            const precio = Math.round(costo + (costo * (margen / 100)) + logistica);
            row.querySelector('[name="f14_precio[]"]').value = precio > 0 ? precio : '';
        }

        function calcIngresos(input) {
            const row = input.closest('tr');
            const cant = parseFloat(row.querySelector('[name="f15_cantidad[]"]').value) || 0;
            const precio = parseFloat(row.querySelector('[name="f15_precio[]"]').value) || 0;
            const ingresos = Math.round(cant * precio);
            row.querySelector('[name="f15_ingresos[]"]').value = ingresos > 0 ? ingresos : '';
        }

        function calcInv(i) {
            const valUnit = parseFloat(document.querySelector(`[name="f16_valunit_${i}"]`).value) || 0;
            const qty = parseFloat(document.querySelector(`[name="f16_cant_${i}"]`).value) || 0;
            const valTotal = Math.round(valUnit * qty);
            document.querySelector(`[name="f16_total_${i}"]`).value = valTotal > 0 ? valTotal : '';
        }

        function calcInversion(input) { // Keep just in case
            const row = input.closest('tr');
            const valUnit = parseFloat(row.querySelector('[name="f16_val_unit[]"]').value) || 0;
            const qty = parseFloat(row.querySelector('[name="f16_cantidad[]"]').value) || 0;
            const valTotal = Math.round(valUnit * qty);
            row.querySelector('[name="f16_val_total[]"]').value = valTotal > 0 ? valTotal : '';
        }

        function calcNeto(mesNum) {
            const ingreso = parseFloat(document.querySelector(`[name="f18_ingreso_m${mesNum}"]`).value) || 0;
            const gprod = parseFloat(document.querySelector(`[name="f18_gprod_m${mesNum}"]`).value) || 0;
            const gamb = parseFloat(document.querySelector(`[name="f18_gamb_m${mesNum}"]`).value) || 0;
            const glog = parseFloat(document.querySelector(`[name="f18_glog_m${mesNum}"]`).value) || 0;

            const neto = ingreso - (gprod + gamb + glog);
            document.querySelector(`[name="f18_neto_m${mesNum}"]`).value = neto;
        }

        // Form Submit
        async function submitForm() {
            if (!selectedProducerId) {
                alert("Por favor selecciona un productor antes de guardar.");
                return;
            }

            const submitBtn = document.getElementById('btn-next');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = "Guardando...";

            const prod = allProducers.find(p => p.id == selectedProducerId);
            if (!prod) {
                alert("Error: No se encontró la información del productor seleccionado.");
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            try {
                // 1. Guardar/actualizar los datos del productor en la base de datos (si se editaron)
                const editPayload = {
                    id: selectedProducerId,
                    nombre: prod.nombre_completo,
                    tipo_documento: prod.tipo_documento,
                    cedula: prod.numero_documento,
                    fecha_nacimiento: document.getElementById('edit-fecha-nacimiento').value || '1900-01-01',
                    telefono: document.getElementById('edit-telefono').value || '-',
                    correo: document.getElementById('edit-correo').value || '',
                    vereda: document.getElementById('edit-vereda').value || '-',
                    nombre_predio: document.getElementById('edit-predio').value || '-',
                    nombre_organizacion: document.getElementById('f01_nombre_organizacion').value
                };

                const updateRes = await fetch(API_BASE + 'api/update_productor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(editPayload)
                });
                
                const updateResult = await updateRes.json();
                if (!updateRes.ok || !updateResult.success) {
                    throw new Error(updateResult.error || 'Error al actualizar los datos del productor.');
                }

                // Update the local allProducers array with the new edited values so they remain updated in memory
                prod.vereda = editPayload.vereda;
                prod.nombre_predio = editPayload.nombre_predio;
                prod.fecha_nacimiento = editPayload.fecha_nacimiento;
                prod.telefono = editPayload.telefono;
                prod.correo_electronico = editPayload.correo;
                prod.nombre_organizacion = editPayload.nombre_organizacion;

            } catch (err) {
                console.error("Error updating producer:", err);
                document.getElementById('error-modal-text').textContent = 'Error al actualizar los datos del productor: ' + err.message;
                document.getElementById('error-modal').classList.add('active');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            // Construct structured JSON object
            const formData = new FormData(document.getElementById('pmapc-form'));
            const payload = {
                productor_id: selectedProducerId,
                data: {
                    f01: {
                        nombre_organizacion: formData.get('f01_nombre_organizacion'),
                        tipo_actividad: formData.get('f01_tipo_actividad'),
                        ubicacion: formData.get('f01_ubicacion'),
                        coordenadas: formData.get('f01_coordenadas'),
                        producto_principal: formData.get('f01_producto_principal'),
                        estado_actual: formData.get('f01_estado_actual'),
                        descripcion_general: formData.get('f01_descripcion_general')
                    },
                    f02: {
                        mision: formData.get('f02_mision'),
                        vision: formData.get('f02_vision'),
                        valores: formData.get('f02_valores')
                    },
                    f03: {
                        problema: formData.get('f03_problema'),
                        solucion: formData.get('f03_solucion'),
                        diferencial: formData.get('f03_diferencial'),
                        valor_ambiental: formData.get('f03_valor_ambiental'),
                        valor_social: formData.get('f03_valor_social'),
                        demostracion: formData.get('f03_demostracion')
                    },
                    f04: {
                        fortalezas: formData.get('f04_fortalezas'),
                        oportunidades: formData.get('f04_oportunidades'),
                        debilidades: formData.get('f04_debilidades'),
                        amenazas: formData.get('f04_amenazas')
                    },
                    f05: getTableDataF05(),
                    f06: {
                        necesidad: formData.get('f06_necesidad'),
                        como_sabe: formData.get('f06_como_sabe'),
                        a_quien_afecta: formData.get('f06_a_quien_afecta'),
                        evidencia: formData.get('f06_evidencia'),
                        oportunidad_organicos: formData.get('f06_oportunidad_organicos'),
                        cambio: formData.get('f06_cambio'),
                        dificultad: formData.get('f06_dificultad')
                    },
                    f07: getTableDataF07(),
                    f08: {
                        quien_degus: formData.get('f08_quien_degus'),
                        resultado_degus: formData.get('f08_resultado_degus'),
                        motivacion_degus: formData.get('f08_motivacion_degus'),
                        evidencia_degus: formData.get('f08_evidencia_degus'),

                        quien_ventas: formData.get('f08_quien_ventas'),
                        resultado_ventas: formData.get('f08_resultado_ventas'),
                        motivacion_ventas: formData.get('f08_motivacion_ventas'),
                        evidencia_ventas: formData.get('f08_evidencia_ventas'),

                        quien_cartas: formData.get('f08_quien_cartas'),
                        resultado_cartas: formData.get('f08_resultado_cartas'),
                        motivacion_cartas: formData.get('f08_motivacion_cartas'),
                        evidencia_cartas: formData.get('f08_evidencia_cartas'),

                        quien_encuesta: formData.get('f08_quien_encuesta'),
                        resultado_encuesta: formData.get('f08_resultado_encuesta'),
                        motivacion_encuesta: formData.get('f08_motivacion_encuesta'),
                        evidencia_encuesta: formData.get('f08_evidencia_encuesta'),

                        quien_entrevista: formData.get('f08_quien_entrevista'),
                        resultado_entrevista: formData.get('f08_resultado_entrevista'),
                        motivacion_entrevista: formData.get('f08_motivacion_entrevista'),
                        evidencia_entrevista: formData.get('f08_evidencia_entrevista'),

                        quien_feria: formData.get('f08_quien_feria'),
                        resultado_feria: formData.get('f08_resultado_feria'),
                        motivacion_feria: formData.get('f08_motivacion_feria'),
                        evidencia_feria: formData.get('f08_evidencia_feria'),

                        metodo_otro: formData.get('f08_metodo_otro'),
                        quien_otro: formData.get('f08_quien_otro'),
                        resultado_otro: formData.get('f08_resultado_otro'),
                        motivacion_otro: formData.get('f08_motivacion_otro'),
                        evidencia_otro: formData.get('f08_evidencia_otro')
                    },
                    f09: getTableDataF09(),
                    f10: getTableDataF10(),
                    f11: getTableDataF11(),
                    f12: {
                        produccion_estimada: formData.get('f12_produccion_estimada'),
                        produccion_maxima: formData.get('f12_produccion_maxima'),
                        area: formData.get('f12_area'),
                        limitantes_prod: formData.get('f12_limitantes_prod'),
                        limitantes_amb: formData.get('f12_limitantes_amb'),
                        capacidad_instalada: formData.get('f12_capacidad_instalada'),
                        capacidad_utilizada: formData.get('f12_capacidad_utilizada'),
                        misma_cantidad: formData.get('f12_misma_cantidad'),
                        alcanza_demanda: formData.get('f12_alcanza_demanda'),
                        necesidad_sostenible: formData.get('f12_necesidad_sostenible')
                    },
                    f12a: {
                        estado_agua: formData.get('f12a_estado_agua'),
                        limite_agua: formData.get('f12a_limite_agua'),
                        efecto_agua: formData.get('f12a_efecto_agua'),
                        accion_agua: formData.get('f12a_accion_agua'),

                        estado_fuentes: formData.get('f12a_estado_fuentes'),
                        limite_fuentes: formData.get('f12a_limite_fuentes'),
                        efecto_fuentes: formData.get('f12a_efecto_fuentes'),
                        accion_fuentes: formData.get('f12a_accion_fuentes'),

                        estado_suelo: formData.get('f12a_estado_suelo'),
                        limite_suelo: formData.get('f12a_limite_suelo'),
                        efecto_suelo: formData.get('f12a_efecto_suelo'),
                        accion_suelo: formData.get('f12a_accion_suelo'),

                        estado_pendiente: formData.get('f12a_estado_pendiente'),
                        limite_pendiente: formData.get('f12a_limite_pendiente'),
                        efecto_pendiente: formData.get('f12a_efecto_pendiente'),
                        accion_pendiente: formData.get('f12a_accion_pendiente'),

                        estado_clima: formData.get('f12a_estado_clima'),
                        limite_clima: formData.get('f12a_limite_clima'),
                        efecto_clima: formData.get('f12a_efecto_clima'),
                        accion_clima: formData.get('f12a_accion_clima'),

                        estado_bio: formData.get('f12a_estado_bio'),
                        limite_bio: formData.get('f12a_limite_bio'),
                        efecto_bio: formData.get('f12a_efecto_bio'),
                        accion_bio: formData.get('f12a_accion_bio'),

                        estado_insumos: formData.get('f12a_estado_insumos'),
                        limite_insumos: formData.get('f12a_limite_insumos'),
                        efecto_insumos: formData.get('f12a_efecto_insumos'),
                        accion_insumos: formData.get('f12a_accion_insumos'),

                        estado_residuos: formData.get('f12a_estado_residuos'),
                        limite_residuos: formData.get('f12a_limite_residuos'),
                        efecto_residuos: formData.get('f12a_efecto_residuos'),
                        accion_residuos: formData.get('f12a_accion_residuos')
                    },
                    f12b: getTableDataF12B(),
                    f12c: getTableDataF12C(),
                                        f13: getTableDataF13(),
                    f14: getTableDataF14(),
                    f15: getTableDataF15(),
                    f15a: getTableDataF15A(),
                    f15b: getTableDataF15B(),
                    f15c: getTableDataF15C(),
                    f16: getTableDataF16(),
                    f17: getTableDataF17(),
                    f18: getTableDataF18(),
                                        f19_conclusion: document.getElementById('f19_conclusion').value,
                    f19: getTableDataF19(),
                    f19a_conclusion: document.getElementById('f19a_conclusion').value,
                    f19a: getTableDataF19A(),
                    f20_conclusion: document.getElementById('f20_conclusion').value,
                    f20: getTableDataF20(),
                    f21_conclusion: document.getElementById('f21_conclusion').value,
                    f22: getTableDataF22(),
                    f22a_conclusion: document.getElementById('f22a_conclusion').value,
                    f22a: getTableDataF22A(),
                    f23: getTableDataF23(),
                    f24: getTableDataF24(),
                    f25: getTableDataF25(),
                    f26: getTableDataF26(),
                    f26_coherencia: document.getElementById('f26_coherencia').value
                }
            };

            try {
                // Save locally first to always have a cache
                localStorage.setItem(`pmapc_data_${productorId}`, JSON.stringify(payload.data));

                const res = await fetch(API_BASE + 'api/submit_pmapc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await res.json();
                if (res.ok && result.success) {
                    removePendingSync(productorId);
                    
                    // Restore standard success modal text
                    document.getElementById('success-title').textContent = "¡Guardado Exitoso!";
                    document.getElementById('success-message').textContent = "El Plan de Manejo Ambiental, Productivo y Comercial (PMAPC) ha sido guardado correctamente en la base de datos.";
                    document.getElementById('success-modal').classList.add('active');
                } else {
                    addPendingSync(productorId, payload);
                    showOfflineSuccessModal(result.error || 'Ocurrió un error al guardar en el servidor.');
                }
            } catch (err) {
                console.error("Submit error:", err);
                addPendingSync(productorId, payload);
                showOfflineSuccessModal('Error de red. Guardado localmente en el dispositivo de forma segura.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                updateSyncBanner();
            }
        }

        // Table array parsing helpers
        function getTableDataF05() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f05').forEach(row => {
                const actor = row.querySelector('[name="f05_actor[]"]').value.trim();
                if (actor) {
                    data.push({
                        actor: actor,
                        perfil: row.querySelector('[name="f05_perfil[]"]').value,
                        ubicacion: row.querySelector('[name="f05_ubicacion[]"]').value,
                        necesidad: row.querySelector('[name="f05_necesidad[]"]').value,
                        frecuencia: row.querySelector('[name="f05_frecuencia[]"]').value,
                        criterio: row.querySelector('[name="f05_criterio[]"]').value,
                        canal: row.querySelector('[name="f05_canal[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF07() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f07').forEach(row => {
                const actor = row.querySelector('[name="f07_actor[]"]').value.trim();
                if (actor) {
                    data.push({
                        actor: actor,
                        aporta: row.querySelector('[name="f07_aporta[]"]').value,
                        recibe: row.querySelector('[name="f07_recibe[]"]').value,
                        trabajo: row.querySelector('[name="f07_trabajo[]"]').value,
                        ambiental: row.querySelector('[name="f07_ambiental[]"]').value,
                        accion: row.querySelector('[name="f07_accion[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF09() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f09').forEach(row => {
                const producto = row.querySelector('[name="f09_producto[]"]').value.trim();
                if (producto) {
                    data.push({
                        producto: producto,
                        descripcion: row.querySelector('[name="f09_descripcion[]"]').value,
                        unidad: row.querySelector('[name="f09_unidad[]"]').value,
                        insumos: row.querySelector('[name="f09_insumos[]"]').value,
                        almacenamiento: row.querySelector('[name="f09_almacenamiento[]"]').value,
                        presentacion: row.querySelector('[name="f09_presentacion[]"]').value,
                        diferencial: row.querySelector('[name="f09_diferencial[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF10() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f10').forEach(row => {
                const bien = row.querySelector('[name="f10_bien[]"]').value.trim();
                if (bien) {
                    data.push({
                        bien: bien,
                        unidades: row.querySelector('[name="f10_unidades[]"]').value,
                        actividad: row.querySelector('[name="f10_actividad[]"]').value,
                        tiempo: row.querySelector('[name="f10_tiempo[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF11() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f11').forEach(row => {
                const insumo = row.querySelector('[name="f11_insumo[]"]').value.trim();
                if (insumo) {
                    data.push({
                        insumo: insumo,
                        cantidad: row.querySelector('[name="f11_cantidad[]"]').value,
                        frecuencia: row.querySelector('[name="f11_frecuencia[]"]').value,
                        proveedor: row.querySelector('[name="f11_proveedor[]"]').value,
                        toxicidad: row.querySelector('[name="f11_toxicidad[]"]').value,
                        impacto: row.querySelector('[name="f11_impacto[]"]').value,
                        manejo: row.querySelector('[name="f11_manejo[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF12B() {
            const data = {};
            const keys = ['virus', 'bacterias', 'picaduras', 'mordeduras', 'temperatura', 'radiacion', 'ruido', 'polvos', 'gases', 'particulado', 'posturas', 'movimientos', 'cargas', 'mecanico', 'locativo', 'electrico', 'transito'];
            keys.forEach(k => {
                const siBox = document.querySelector(`[name="f12b_${k}_si"]`);
                const noBox = document.querySelector(`[name="f12b_${k}_no"]`);
                const altaBox = document.querySelector(`[name="f12b_${k}_f_alta"]`);
                const mediaBox = document.querySelector(`[name="f12b_${k}_f_media"]`);
                const bajaBox = document.querySelector(`[name="f12b_${k}_f_baja"]`);
                const controles = document.querySelector(`[name="f12b_${k}_controles"]`)?.value;
                const mejora = document.querySelector(`[name="f12b_${k}_mejora"]`)?.value;
                
                data[k] = {
                    si: siBox ? siBox.checked : false,
                    no: noBox ? noBox.checked : false,
                    f_alta: altaBox ? altaBox.checked : false,
                    f_media: mediaBox ? mediaBox.checked : false,
                    f_baja: bajaBox ? bajaBox.checked : false,
                    controles: controles || '',
                    mejora: mejora || ''
                };
            });
            return data;
        }

        function getTableDataF12C() {
            const data = {};
            for (let i = 1; i <= 7; i++) {
                data[i] = {
                    resp: document.querySelector(`[name="f12c_resp_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f12c_frec_${i}"]`)?.value || '',
                    evidencia: document.querySelector(`[name="f12c_evidencia_${i}"]`)?.value || ''
                };
            }
            return data;
        }

                function getTableDataF13() {
            const arr = [];
            for (let i = 0; i <= 7; i++) {
                arr.push({
                    aplica: document.querySelector(`[name="f13_aplica_${i}"]`)?.value || 'No',
                    detalles: document.querySelector(`[name="f13_detalles_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f13_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f13_resp_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF14() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f14').forEach(row => {
                const producto = row.querySelector('[name="f14_producto[]"]').value.trim();
                if (producto) {
                    arr.push({
                        producto: producto,
                        costo: row.querySelector('[name="f14_costo[]"]').value,
                        margen: row.querySelector('[name="f14_margen[]"]').value,
                        pmin: row.querySelector('[name="f14_pmin[]"]').value,
                        pmercado: row.querySelector('[name="f14_pmercado[]"]').value,
                        logistica: row.querySelector('[name="f14_logistica[]"]').value,
                        precio: row.querySelector('[name="f14_precio[]"]').value,
                        justificacion: row.querySelector('[name="f14_justificacion[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF15() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f15').forEach(row => {
                const producto = row.querySelector('[name="f15_producto[]"]').value.trim();
                if (producto) {
                    arr.push({
                        producto: producto,
                        cantidad: row.querySelector('[name="f15_cantidad[]"]').value,
                        precio: row.querySelector('[name="f15_precio[]"]').value,
                        ingresos: row.querySelector('[name="f15_ingresos[]"]').value,
                        pago: row.querySelector('[name="f15_pago[]"]').value,
                        cliente: row.querySelector('[name="f15_cliente[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF15A() {
            const arr = [];
            for (let i = 0; i <= 4; i++) {
                arr.push({
                    est: document.querySelector(`[name="f15a_est_${i}"]`)?.value || '',
                    med: document.querySelector(`[name="f15a_med_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f15a_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f15a_resp_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF15B() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f15b').forEach(row => {
                const prod = row.querySelector('[name="f15b_prod[]"]').value.trim();
                if (prod) {
                    arr.push({
                        prod: prod,
                        canal: row.querySelector('[name="f15b_canal[]"]').value,
                        tiempo: row.querySelector('[name="f15b_tiempo[]"]').value,
                        transporte: row.querySelector('[name="f15b_transporte[]"]').value,
                        condicion: row.querySelector('[name="f15b_condicion[]"]').value,
                        capacidad: row.querySelector('[name="f15b_capacidad[]"]').value,
                        costo: row.querySelector('[name="f15b_costo[]"]').value,
                        resp: row.querySelector('[name="f15b_resp[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF15C() {
            const arr = [];
            for (let i = 0; i <= 4; i++) {
                arr.push({
                    resp: document.querySelector(`[name="f15c_resp_${i}"]`)?.value || '',
                    med: document.querySelector(`[name="f15c_med_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f15c_frec_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f15c_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF16() {
            const arr = [];
            for (let i = 0; i <= 8; i++) {
                arr.push({
                    desc: document.querySelector(`[name="f16_desc_${i}"]`)?.value || '',
                    valunit: document.querySelector(`[name="f16_valunit_${i}"]`)?.value || '',
                    cant: document.querySelector(`[name="f16_cant_${i}"]`)?.value || '',
                    total: document.querySelector(`[name="f16_total_${i}"]`)?.value || '',
                    req: document.querySelector(`[name="f16_req_${i}"]`)?.value || '',
                    fuente: document.querySelector(`[name="f16_fuente_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF17() {
            const arr = [];
            for (let i = 0; i <= 5; i++) {
                arr.push({
                    desc: document.querySelector(`[name="f17_desc_${i}"]`)?.value || '',
                    val: document.querySelector(`[name="f17_val_${i}"]`)?.value || '',
                    obs: document.querySelector(`[name="f17_obs_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF18() {
            const data = {};
            for (let i = 1; i <= 6; i++) {
                data[`ingreso_m${i}`] = document.querySelector(`[name="f18_ingreso_m${i}"]`).value;
                data[`gprod_m${i}`] = document.querySelector(`[name="f18_gprod_m${i}"]`).value;
                data[`gamb_m${i}`] = document.querySelector(`[name="f18_gamb_m${i}"]`).value;
                data[`glog_m${i}`] = document.querySelector(`[name="f18_glog_m${i}"]`).value;
                data[`neto_m${i}`] = document.querySelector(`[name="f18_neto_m${i}"]`).value;
            }
            return data;
        }

        function getTableDataF23() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f23').forEach(row => {
                const riesgo = row.querySelector('[name="f23_riesgo[]"]').value.trim();
                if (riesgo) {
                    arr.push({
                        tipo: row.querySelector('[name="f23_tipo[]"]').value,
                        riesgo: riesgo,
                        causa: row.querySelector('[name="f23_causa[]"]').value,
                        consecuencia: row.querySelector('[name="f23_consecuencia[]"]').value,
                        nivel: row.querySelector('[name="f23_nivel[]"]').value,
                        prevencion: row.querySelector('[name="f23_prevencion[]"]').value,
                        respuesta: row.querySelector('[name="f23_respuesta[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF24() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f24').forEach(row => {
                const act = row.querySelector('[name="f24_actividad[]"]').value.trim();
                if (act) {
                    data.push({
                        actividad: act,
                        componente: row.querySelector('[name="f24_componente[]"]').value,
                        responsable: row.querySelector('[name="f24_responsable[]"]').value,
                        tiempo: row.querySelector('[name="f24_tiempo[]"]').value,
                        resultado: row.querySelector('[name="f24_resultado[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF25() {
            const arr = [];
            for (let i = 0; i <= 8; i++) {
                arr.push({
                    ind: document.querySelector(`[name="f25_ind_${i}"]`)?.value || '',
                    meta: document.querySelector(`[name="f25_meta_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f25_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f25_resp_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f25_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF26() {
            const arr = [];
            for (let i = 0; i <= 8; i++) {
                arr.push({
                    prod: document.querySelector(`[name="f26_prod_${i}"]`)?.value || '',
                    com: document.querySelector(`[name="f26_com_${i}"]`)?.value || '',
                    fin: document.querySelector(`[name="f26_fin_${i}"]`)?.value || '',
                    amb: document.querySelector(`[name="f26_amb_${i}"]`)?.value || '',
                    aju: document.querySelector(`[name="f26_aju_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        async function logout() {
            try {
                await fetch(API_BASE + 'api/logout.php');
                window.location.reload();
            } catch (error) {
                console.error('Logout error:', error);
            }
        }

        // Offline storage & sync helpers
        function getPendingSyncs() {
            const raw = localStorage.getItem('pending_sync_pmapc');
            return raw ? JSON.parse(raw) : [];
        }

        function addPendingSync(productorId, payload) {
            let list = getPendingSyncs();
            if (!list.includes(productorId)) {
                list.push(productorId);
                localStorage.setItem('pending_sync_pmapc', JSON.stringify(list));
            }
            localStorage.setItem(`pmapc_offline_payload_${productorId}`, JSON.stringify(payload));
        }

        function removePendingSync(productorId) {
            let list = getPendingSyncs();
            list = list.filter(id => id != productorId);
            localStorage.setItem('pending_sync_pmapc', JSON.stringify(list));
            localStorage.removeItem(`pmapc_offline_payload_${productorId}`);
        }

        function updateSyncBanner() {
            const list = getPendingSyncs();
            const banner = document.getElementById('sync-banner');
            const bannerText = document.getElementById('sync-banner-text');
            if (banner && bannerText) {
                if (list.length > 0) {
                    banner.style.display = 'block';
                    bannerText.textContent = `Tienes ${list.length} registro(s) guardados sin conexión pendientes de sincronizar.`;
                } else {
                    banner.style.display = 'none';
                }
            }
        }

        function showOfflineSuccessModal(message) {
            document.getElementById('success-title').textContent = "¡Guardado Localmente!";
            document.getElementById('success-message').textContent = message || "El PMAPC ha sido guardado de forma segura en este dispositivo (sin conexión). Se sincronizará automáticamente con el servidor cuando haya acceso a Internet.";
            document.getElementById('success-modal').classList.add('active');
        }

        function showOfflineNotice(msg) {
            const toast = document.getElementById('offline-toast');
            const toastText = document.getElementById('offline-toast-text');
            if (toast && toastText) {
                toastText.textContent = msg;
                toast.style.display = 'flex';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 4000);
            }
        }

        async function syncPendingRecords() {
            const list = getPendingSyncs();
            if (list.length === 0) return;

            const syncBtn = document.querySelector('#sync-banner button');
            const originalText = syncBtn ? syncBtn.textContent : "Sincronizar ahora";
            if (syncBtn) {
                syncBtn.disabled = true;
                syncBtn.textContent = "Sincronizando...";
            }

            let successCount = 0;
            let failCount = 0;

            for (const productorId of list) {
                const rawPayload = localStorage.getItem(`pmapc_offline_payload_${productorId}`);
                if (!rawPayload) {
                    removePendingSync(productorId);
                    continue;
                }

                try {
                    const payload = JSON.parse(rawPayload);
                    const res = await fetch(API_BASE + 'api/submit_pmapc.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await res.json();
                    if (res.ok && result.success) {
                        removePendingSync(productorId);
                        successCount++;
                    } else {
                        failCount++;
                    }
                } catch (err) {
                    console.error("Failed to sync productor " + productorId, err);
                    failCount++;
                }
            }

            if (syncBtn) {
                syncBtn.disabled = false;
                syncBtn.textContent = originalText;
            }
            updateSyncBanner();

            if (successCount > 0) {
                alert(`Sincronización finalizada: se subieron ${successCount} registros exitosamente.${failCount > 0 ? ` Fallaron ${failCount} registros.` : ''}`);
            } else if (failCount > 0) {
                alert(`No se pudieron sincronizar algunos registros. Por favor verifica tu conexión a Internet.`);
            }
        }

        // Backup and Restore Helpers
        function openBackupModal() {
            document.getElementById('backup-modal').style.display = 'flex';
        }

        function closeBackupModal() {
            document.getElementById('backup-modal').style.display = 'none';
        }

        function getBackupPayload() {
            const backup = {
                timestamp: new Date().toISOString(),
                source: "SomosSumapaz_PMAPC_Backup",
                localStorageData: {}
            };
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key === 'cached_productores' || 
                    key === 'pending_sync_pmapc' || 
                    key.startsWith('pmapc_data_') || 
                    key.startsWith('pmapc_offline_payload_')) {
                    backup.localStorageData[key] = localStorage.getItem(key);
                }
            }
            return JSON.stringify(backup, null, 2);
        }

        function exportBackupFile() {
            const payload = getBackupPayload();
            if (window.AndroidInterface && typeof window.AndroidInterface.saveBackupFile === 'function') {
                window.AndroidInterface.saveBackupFile(payload);
            } else {
                const blob = new Blob([payload], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `respaldo_pmapc_${new Date().toISOString().slice(0,10)}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        }

        function shareBackupText() {
            const payload = getBackupPayload();
            if (window.AndroidInterface && typeof window.AndroidInterface.shareBackupText === 'function') {
                window.AndroidInterface.shareBackupText(payload);
            } else {
                copyBackupToClipboard();
            }
        }

        function copyBackupToClipboard() {
            const payload = getBackupPayload();
            navigator.clipboard.writeText(payload).then(() => {
                alert("Copia de seguridad copiada al portapapeles en formato JSON.");
            }).catch(err => {
                alert("Error al copiar al portapapeles: " + err);
            });
        }

        function handleRestoreFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (!confirm("¿Estás seguro de que deseas restaurar esta copia de seguridad? Esto reemplazará todos tus datos guardados localmente.")) {
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const backup = JSON.parse(e.target.result);
                    if (backup.source !== "SomosSumapaz_PMAPC_Backup" || !backup.localStorageData) {
                        alert("El archivo seleccionado no es una copia de seguridad válida de PMAPC.");
                        return;
                    }

                    const data = backup.localStorageData;
                    Object.keys(data).forEach(key => {
                        localStorage.setItem(key, data[key]);
                    });

                    alert("¡Copia de seguridad restaurada con éxito! La página se recargará ahora.");
                    window.location.reload();
                } catch(err) {
                    alert("Error al leer el archivo de respaldo: " + err.message);
                }
            };
            reader.readAsText(file);
        }

        function handleLogoClick(event) {
            event.preventDefault();
            if (window.location.protocol === 'file:') {
                window.location.reload();
            } else {
                window.location.href = 'index.html';
            }
        }

        // Auto sync listeners
        window.addEventListener('online', () => {
            syncPendingRecords();
        });

        // Initialize banner on DOM load
        document.addEventListener('DOMContentLoaded', () => {
            updateSyncBanner();
            if (navigator.onLine) {
                setTimeout(syncPendingRecords, 1500);
            }
        });
    </script>
</body>

</html>
