<?php
/**
 * Clase Mailer para el envío de correos de revisión
 */

require_once __DIR__ . '/../config.php';

// Cargar el autoloader de Composer si existe
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer {

    private static string $lastMailError = '';

    public static function getLastMailError(): string {
        return self::$lastMailError;
    }

    /**
     * Envía el correo de revisión a un cliente con diseño premium
     * @param array $cliente Fila del cliente de la BD
     * @param array $post Fila del post de la BD
     * @return bool
     */
    public static function sendReviewEmail(array $cliente, array $post): bool {
        $token = $post['token_revision'];
        // Generar URL del revisor usando la constante BASE_URL de config.php
        $reviewUrl = BASE_URL . "/platform/admin/posts/revisar.php?token=" . $token;

        $subject = "Nuevo post listo para revisar: " . $post['titulo'];

        // Construir cuerpo de correo con estilos del cliente
        $logoHtml = !empty($cliente['logo_url']) 
            ? "<img src='{$cliente['logo_url']}' alt='{$cliente['nombre']}' style='max-height: 60px; margin-bottom: 20px; border-radius: 8px;' />" 
            : "<h2 style='color: {$cliente['color_primario']}; margin: 0 0 20px 0; font-family: {$cliente['fuente_titulo']};'>{$cliente['nombre']}</h2>";

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>{$subject}</title>
        </head>
        <body style=\"margin: 0; padding: 0; background-color: #F4F6F9; font-family: {$cliente['fuente_texto']}, sans-serif; color: #333333; -webkit-font-smoothing: antialiased;\">
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='table-layout: fixed;'>
                <tr>
                    <td align='center' style='padding: 40px 10px;'>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                            <!-- Cabecera de la marca -->
                            <tr>
                                <td align='center' style='padding: 30px 40px; background-color: #111827; border-bottom: 3px solid {$cliente['color_primario']};'>
                                    {$logoHtml}
                                    <div style='color: #9CA3AF; font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px;'>Revisión de Contenido</div>
                                </td>
                            </tr>
                            
                            <!-- Contenido del Mensaje -->
                            <tr>
                                <td style='padding: 40px 40px 30px 40px;'>
                                    <p style='margin: 0 0 16px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                        Hola <strong>{$cliente['nombre_autor']}</strong>,
                                    </p>
                                    <p style='margin: 0 0 24px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                        Hemos generado una nueva entrada para tu blog sobre el tema: <strong>\"{$post['tema']}\"</strong>. Por favor, revísala y dinos si deseas aprobarla o realizar ajustes.
                                    </p>
                                    
                                    <!-- Caja con la previsualización del título -->
                                    <div style='background-color: #F9FAFB; border-left: 4px solid {$cliente['color_primario']}; padding: 20px; margin-bottom: 30px; border-radius: 0 8px 8px 0;'>
                                        <div style='font-size: 12px; text-transform: uppercase; color: #9CA3AF; letter-spacing: 1px; margin-bottom: 5px;'>Título Propuesto</div>
                                        <h3 style='margin: 0; color: {$cliente['color_texto']}; font-family: {$cliente['fuente_titulo']}; font-size: 20px;'>{$post['titulo']}</h3>
                                    </div>
                                    
                                    <!-- Botón de Acción -->
                                    <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                        <tr>
                                            <td align='center' style='padding: 10px 0 30px 0;'>
                                                <a href='{$reviewUrl}' target='_blank' style=\"display: inline-block; background-color: {$cliente['color_primario']}; color: #ffffff; text-decoration: none; padding: 16px 36px; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-shadow: 0 1px 1px rgba(0,0,0,0.1);\">Ver y Evaluar Post</a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <hr style='border: 0; border-top: 1px solid #E5E7EB; margin: 0 0 20px 0;' />
                                    <p style='margin: 0; font-size: 13px; color: #9CA3AF; line-height: 1.5;'>
                                        Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
                                        <a href='{$reviewUrl}' style='color: {$cliente['color_primario']}; word-break: break-all;'>{$reviewUrl}</a>
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Pie de página del correo -->
                            <tr>
                                <td style='padding: 24px 40px; background-color: #F9FAFB; text-align: center; font-size: 12px; color: #9CA3AF;'>
                                    Este es un correo automático de tu plataforma AI Blogger.<br>
                                    Dominio del cliente registrado: <a href='{$cliente['dominio']}' style='color: #6B7280;'>{$cliente['dominio']}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        // Intentar enviar con PHPMailer si está cargado
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new PHPMailer(true);
            try {
                // Configuración SMTP
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';

                // Destinatarios
                $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                $mail->addAddress($cliente['email_revisor'], $cliente['nombre_autor']);

                // Contenido
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                return true;
            } catch (PHPMailerException $e) {
                self::$lastMailError = "PHPMailer Error: " . $mail->ErrorInfo;
                error_log(self::$lastMailError);
                // Si falla SMTP, continuar con el fallback nativo de PHP
            }
        }

        // Fallback: función mail() nativa de PHP
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";

        $sent = mail($cliente['email_revisor'], $subject, $body, $headers);
        if (!$sent) {
            self::$lastMailError = empty(self::$lastMailError) 
                ? "Fallback mail() falló al enviar correo." 
                : self::$lastMailError . " | Fallback mail() también falló.";
            error_log("Fallback mail() también falló al enviar correo a: " . $cliente['email_revisor']);
        }
        return $sent;
    }

    /**
     * Envía un correo con sugerencias de temas al cliente
     * @param array $cliente Fila del cliente de la BD
     * @param array $temas Lista de temas sugeridos (strings)
     * @return bool
     */
    public static function sendTopicSuggestionsEmail(array $cliente, array $temas): bool {
        $subject = "Ideas de temas sugeridos para tu blog: " . $cliente['nombre'];

        // Construir cuerpo de correo con estilos del cliente
        $logoHtml = !empty($cliente['logo_url']) 
            ? "<img src='{$cliente['logo_url']}' alt='{$cliente['nombre']}' style='max-height: 60px; margin-bottom: 20px; border-radius: 8px;' />" 
            : "<h2 style='color: {$cliente['color_primario']}; margin: 0 0 20px 0; font-family: {$cliente['fuente_titulo']};'>{$cliente['nombre']}</h2>";

        $temasHtml = '';
        foreach ($temas as $i => $item) {
            $num = $i + 1;
            // Obtener token de acceso del cliente (api_key_sitio) para autenticación automática
            $clientToken = $cliente['api_key_sitio'] ?? '';
            if (is_array($item) && isset($item['id'])) {
                $generateUrl = BASE_URL . "/platform/admin/posts/generar.php?sugerencia_id=" . $item['id'] . "&token=" . urlencode($clientToken);
                $temaText = $item['tema'];
            } else {
                $generateUrl = BASE_URL . "/platform/admin/posts/generar.php?cliente_id=" . $cliente['id'] . "&tema=" . urlencode($item) . "&token=" . urlencode($clientToken);
                $temaText = $item;
            }
            $temasHtml .= "
            <div style='background-color: #F9FAFB; border-left: 4px solid {$cliente['color_primario']}; padding: 18px; margin-bottom: 20px; border-radius: 0 8px 8px 0;'>
                <div style='font-size: 11px; text-transform: uppercase; color: #9CA3AF; letter-spacing: 1px; margin-bottom: 5px;'>Sugerencia #{$num}</div>
                <h4 style='margin: 0 0 12px 0; color: {$cliente['color_texto']}; font-family: {$cliente['fuente_titulo']}; font-size: 16px; line-height: 1.4;'>\"{$temaText}\"</h4>
                <a href='{$generateUrl}' target='_blank' style=\"display: inline-block; background-color: {$cliente['color_primario']}; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; font-size: 13px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);\">Escribir sobre este tema</a>
            </div>";
        }

        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>{$subject}</title>
        </head>
        <body style=\"margin: 0; padding: 0; background-color: #F4F6F9; font-family: {$cliente['fuente_texto']}, sans-serif; color: #333333; -webkit-font-smoothing: antialiased;\">
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='table-layout: fixed;'>
                <tr>
                    <td align='center' style='padding: 40px 10px;'>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                            <!-- Cabecera de la marca -->
                            <tr>
                                <td align='center' style='padding: 30px 40px; background-color: #111827; border-bottom: 3px solid {$cliente['color_primario']};'>
                                    {$logoHtml}
                                    <div style='color: #9CA3AF; font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px;'>Sugerencias semanales de IA</div>
                                </td>
                            </tr>
                            
                            <!-- Contenido del Mensaje -->
                            <tr>
                                <td style='padding: 40px 40px 30px 40px;'>
                                    <p style='margin: 0 0 16px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                        Hola <strong>{$cliente['nombre_autor']}</strong>,
                                    </p>
                                    <p style='margin: 0 0 24px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                        Hemos generado algunas ideas y temas creativos para las próximas entradas del blog de <strong>{$cliente['nombre']}</strong>.
                                    </p>
                                    <p style='margin: 0 0 24px 0; font-size: 15px; line-height: 1.5; color: #6B7280; font-style: italic;'>
                                        Haz clic en el botón \"Escribir sobre este tema\" de la idea que prefieras. Serás redirigido/a al panel para generar automáticamente la entrada y la imagen de cabecera con IA.
                                    </p>
                                    
                                    <!-- Lista de Temas -->
                                    {$temasHtml}
                                    
                                </td>
                            </tr>
                            
                            <!-- Pie de página del correo -->
                            <tr>
                                <td style='padding: 24px 40px; background-color: #F9FAFB; text-align: center; font-size: 12px; color: #9CA3AF;'>
                                    Este es un correo automático de tu plataforma AI Blogger.<br>
                                    Dominio del cliente registrado: <a href='{$cliente['dominio']}' style='color: #6B7280;'>{$cliente['dominio']}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        // Intentar enviar con PHPMailer si está cargado
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new PHPMailer(true);
            try {
                // Configuración SMTP
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';

                // Destinatarios
                $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                $mail->addAddress($cliente['email_revisor'], $cliente['nombre_autor']);

                // Contenido
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                return true;
            } catch (PHPMailerException $e) {
                self::$lastMailError = "PHPMailer Error: " . $mail->ErrorInfo;
                error_log(self::$lastMailError);
                // Si falla SMTP, continuar con el fallback nativo de PHP
            }
        }

        // Fallback: función mail() nativa de PHP
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";

        $sent = mail($cliente['email_revisor'], $subject, $body, $headers);
        if (!$sent) {
            self::$lastMailError = empty(self::$lastMailError) 
                ? "Fallback mail() falló al enviar correo de sugerencia." 
                : self::$lastMailError . " | Fallback mail() también falló.";
            error_log("Fallback mail() también falló al enviar correo de sugerencia a: " . $cliente['email_revisor']);
        }
        return $sent;
    }

    /**
     * Reenvía temas pendientes ya existentes (sin llamar a la IA) como recordatorio.
     * Reutiliza sendTopicSuggestionsEmail internamente.
     * @param array $cliente Fila del cliente de la BD
     * @param array $sugerencias Sugerencias pendientes de la BD [['id' => ..., 'tema' => ...], ...]
     * @return bool
     */
    public static function sendTopicReminderEmail(array $cliente, array $sugerencias): bool {
        // Usar la misma plantilla, pero con un asunto diferente
        $subject = "Recordatorio: Tienes ideas de blog esperándote 💡 — " . $cliente['nombre'];

        $clientToken = $cliente['api_key_sitio'] ?? '';
        $logoHtml = !empty($cliente['logo_url']) 
            ? "<img src='{$cliente['logo_url']}' alt='{$cliente['nombre']}' style='max-height: 60px; margin-bottom: 20px; border-radius: 8px;' />" 
            : "<h2 style='color: {$cliente['color_primario']}; margin: 0 0 20px 0; font-family: {$cliente['fuente_titulo']};'>{$cliente['nombre']}</h2>";

        $temasHtml = '';
        foreach ($sugerencias as $i => $item) {
            $num = $i + 1;
            $generateUrl = BASE_URL . "/platform/admin/posts/generar.php?sugerencia_id=" . $item['id'] . "&token=" . urlencode($clientToken);
            $temaText = $item['tema'];
            $temasHtml .= "
            <div style='background-color: #F9FAFB; border-left: 4px solid {$cliente['color_primario']}; padding: 18px; margin-bottom: 20px; border-radius: 0 8px 8px 0;'>
                <div style='font-size: 11px; text-transform: uppercase; color: #9CA3AF; letter-spacing: 1px; margin-bottom: 5px;'>Idea #{$num}</div>
                <h4 style='margin: 0 0 12px 0; color: {$cliente['color_texto']}; font-family: {$cliente['fuente_titulo']}; font-size: 16px; line-height: 1.4;'>\"{$temaText}\"</h4>
                <a href='{$generateUrl}' target='_blank' style=\"display: inline-block; background-color: {$cliente['color_primario']}; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; font-size: 13px;\">Escribir sobre este tema</a>
            </div>";
        }

        $body = "
        <!DOCTYPE html><html><head><meta charset='utf-8'><title>{$subject}</title></head>
        <body style=\"margin: 0; padding: 0; background-color: #F4F6F9; font-family: {$cliente['fuente_texto']}, sans-serif; color: #333333;\">
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='table-layout: fixed;'>
                <tr><td align='center' style='padding: 40px 10px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                        <tr><td align='center' style='padding: 30px 40px; background-color: #111827; border-bottom: 3px solid {$cliente['color_primario']};'>
                            {$logoHtml}
                            <div style='color: #F59E0B; font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px;'>⏰ Recordatorio de Ideas Pendientes</div>
                        </td></tr>
                        <tr><td style='padding: 40px 40px 30px 40px;'>
                            <p style='margin: 0 0 16px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                Hola <strong>{$cliente['nombre_autor']}</strong>,
                            </p>
                            <p style='margin: 0 0 24px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                Te enviamos las ideas que generamos hace unos días para tu blog. ¡Todavía no has elegido ninguna! Selecciona la que más te inspire y en minutos tendrás un post listo.
                            </p>
                            {$temasHtml}
                        </td></tr>
                        <tr><td style='padding: 24px 40px; background-color: #F9FAFB; text-align: center; font-size: 12px; color: #9CA3AF;'>
                            Este es un recordatorio automático de tu plataforma AI Blogger.
                        </td></tr>
                    </table>
                </td></tr>
            </table>
        </body></html>";

        return self::_sendMail($cliente['email_revisor'], $cliente['nombre_autor'], $subject, $body);
    }

    /**
     * Envía un recordatorio cuando un post lleva más de X días en estado pendiente.
     * @param array $cliente Fila del cliente de la BD
     * @param array $post Fila del post de la BD (con token_revision, titulo, tema, imagen_url)
     * @return bool
     */
    public static function sendPostReminderEmail(array $cliente, array $post): bool {
        $subject = "Tu post está esperando ser publicado 🚀 — " . $cliente['nombre'];

        $clientToken = $cliente['api_key_sitio'] ?? '';
        $logoHtml = !empty($cliente['logo_url']) 
            ? "<img src='{$cliente['logo_url']}' alt='{$cliente['nombre']}' style='max-height: 60px; margin-bottom: 20px; border-radius: 8px;' />" 
            : "<h2 style='color: {$cliente['color_primario']}; margin: 0 0 20px 0; font-family: {$cliente['fuente_titulo']};'>{$cliente['nombre']}</h2>";

        // Determinar si el post tiene imagen o no para decidir adónde redirigir
        $tieneImagen = !empty($post['imagen_url']);
        if ($tieneImagen) {
            // Tiene imagen, va directo a revisar y publicar
            $actionUrl  = BASE_URL . "/platform/admin/posts/generar.php?draft_id=" . $post['id'] . "&token=" . urlencode($clientToken);
            $actionText = "Revisar y Publicar";
            $statusMsg  = "Ya tiene texto e imagen listos. Solo falta que lo revises y lo publiques.";
        } else {
            // Le falta la imagen, va al draft para completar el paso 3
            $actionUrl  = BASE_URL . "/platform/admin/posts/generar.php?draft_id=" . $post['id'] . "&token=" . urlencode($clientToken);
            $actionText = "Agregar Imagen y Publicar";
            $statusMsg  = "El texto ya está redactado. Solo falta elegir o generar una imagen de portada.";
        }

        $fechaCreacion = date('d/m/Y', strtotime($post['fecha_creacion']));

        $body = "
        <!DOCTYPE html><html><head><meta charset='utf-8'><title>{$subject}</title></head>
        <body style=\"margin: 0; padding: 0; background-color: #F4F6F9; font-family: {$cliente['fuente_texto']}, sans-serif; color: #333333;\">
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='table-layout: fixed;'>
                <tr><td align='center' style='padding: 40px 10px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                        <tr><td align='center' style='padding: 30px 40px; background-color: #111827; border-bottom: 3px solid {$cliente['color_primario']};'>
                            {$logoHtml}
                            <div style='color: #34D399; font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px;'>📝 Post Pendiente de Publicación</div>
                        </td></tr>
                        <tr><td style='padding: 40px 40px 30px 40px;'>
                            <p style='margin: 0 0 16px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                Hola <strong>{$cliente['nombre_autor']}</strong>,
                            </p>
                            <p style='margin: 0 0 24px 0; font-size: 16px; line-height: 1.6; color: #4B5563;'>
                                Tienes un post generado el <strong>{$fechaCreacion}</strong> que todavía no ha sido publicado. {$statusMsg}
                            </p>
                            <div style='background-color: #F9FAFB; border-left: 4px solid {$cliente['color_primario']}; padding: 20px; margin-bottom: 30px; border-radius: 0 8px 8px 0;'>
                                <div style='font-size: 12px; text-transform: uppercase; color: #9CA3AF; letter-spacing: 1px; margin-bottom: 5px;'>Post Pendiente</div>
                                <h3 style='margin: 0 0 8px 0; color: {$cliente['color_texto']}; font-family: {$cliente['fuente_titulo']}; font-size: 20px;'>{$post['titulo']}</h3>
                                <div style='font-size: 13px; color: #6B7280;'>Tema: {$post['tema']}</div>
                            </div>
                            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                <tr><td align='center'>
                                    <a href='{$actionUrl}' target='_blank' style=\"display: inline-block; background-color: {$cliente['color_primario']}; color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: bold; font-size: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.07);\">{$actionText} →</a>
                                </td></tr>
                            </table>
                        </td></tr>
                        <tr><td style='padding: 24px 40px; background-color: #F9FAFB; text-align: center; font-size: 12px; color: #9CA3AF;'>
                            Este recordatorio se envía automáticamente cuando un post no se publica en 2 días.
                        </td></tr>
                    </table>
                </td></tr>
            </table>
        </body></html>";

        return self::_sendMail($cliente['email_revisor'], $cliente['nombre_autor'], $subject, $body);
    }

    /**
     * Método interno para enviar correos (evitar duplicar lógica SMTP/fallback).
     */
    private static function _sendMail(string $toEmail, string $toName, string $subject, string $body): bool {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                $mail->addAddress($toEmail, $toName);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
                return true;
            } catch (PHPMailerException $e) {
                self::$lastMailError = "PHPMailer Error: " . $mail->ErrorInfo;
                error_log(self::$lastMailError);
            }
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";
        $sent = mail($toEmail, $subject, $body, $headers);
        if (!$sent) {
            self::$lastMailError = empty(self::$lastMailError)
                ? "Fallback mail() falló." 
                : self::$lastMailError . " | Fallback mail() también falló.";
        }
        return $sent;
    }
}
