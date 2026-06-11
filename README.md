# Plataforma Centralizada de Blogs Automáticos con IA

Esta plataforma centralizada en PHP puro te permite gestionar la generación, revisión y publicación automática de artículos de blog optimizados con IA para múltiples clientes (negocios).

## Características Principales

*   **Generación de Texto con IA:** Utiliza la API de **Google Gemini 1.5 Flash** para escribir artículos con tono de marca, rubro y longitud personalizados.
*   **Generación de Banners:** Genera imágenes de cabecera temáticas y horizontales utilizando **Google Gemini Imagen 3**.
*   **Flujo de Revisión por Email:** Los autores reciben un correo electrónico con estilos premium que incluye un token único para aprobar o rechazar el borrador.
*   **Integración de Doble Opción:**
    *   **Opción A (Con Base de Datos Local):** Publicación remota vía POST con validación de firma `X-API-Key`.
    *   **Opción B (Sin Base de Datos - Más Simple):** Los clientes cargan los artículos en tiempo real consumiendo una API centralizada (GET).
*   **Panel de Administración Premium:** Dashboard con KPI cards, filtros por fecha/cliente/estado y un botón de regeneración automática para posts rechazados.

---

## Requisitos del Sistema

*   **PHP 8.0 o superior**
*   **Servidor Web (como Apache/Nginx)**, incluido en XAMPP.
*   **Base de datos MySQL** (incluida en XAMPP).
*   **Extensiones de PHP habilitadas:**
    *   `pdo_mysql` (Conexión a base de datos)
    *   `curl` (Consultas a la API de Gemini)
    *   `openssl` (Conexión SMTP de PHPMailer)
*   **Composer** (opcional, ya ejecutado para instalar PHPMailer).

---

## Estructura del Proyecto

```
/ia-automatic-blogger/
├── schema.sql                     - Estructura de tablas y cliente semilla
├── README.md                      - Este manual de usuario
├── ejemplo-receptor/              - Código de integración para el sitio del cliente
│   ├── publicar.php               - Receptor POST (Opción A - Con DB Local)
│   └── blog.php                   - Renderizado GET (Opción B - Sin DB Local)
└── platform/
    ├── config.php                 - Configuración global (API Keys, DB, SMTP)
    ├── admin/                     - Vistas del panel de administración
    │   ├── index.php              - Dashboard central
    │   ├── clientes/              - CRUD de Clientes (lista, nuevo, editar)
    │   └── posts/                 - Flujos del Redactor (generar, lista, revisar)
    ├── uploads/banners/           - Directorio de imágenes locales
    ├── api/
    │   └── posts.php              - Endpoint GET público para la opción sin DB
    └── lib/
        ├── DB.php                 - Singleton de conexión PDO
        ├── GeminiClient.php       - Cliente REST de Google Gemini (Flash/Imagen 3)
        └── Mailer.php             - Módulo de envío de correos con PHPMailer
```

---

## Instalación y Configuración

### Paso 1: Configurar la Base de Datos
1. Inicia **XAMPP Control Panel** y activa los módulos **Apache** y **MySQL**.
2. Abre tu gestor de base de datos (por ejemplo, PHPMyAdmin: `http://localhost/phpmyadmin`).
3. Crea una base de datos llamada `ia_automatic_blogger` (con cotejamiento `utf8mb4_unicode_ci`).
4. Importa el archivo `schema.sql` ubicado en la raíz del proyecto para crear las tablas `clientes` y `posts` e insertar el cliente semilla **Adri Hair Style**.

### Paso 2: Configurar Parámetros del Sistema
Abre el archivo `platform/config.php` y actualiza los siguientes valores:

1.  **Credenciales de Base de Datos (si difieren de XAMPP por defecto):**
    ```php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ia_automatic_blogger');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    ```
2.  **API Key de Google Gemini:**
    Genera una clave en [Google AI Studio](https://aistudio.google.com/) y colócala en:
    ```php
    define('GEMINI_API_KEY', 'TU_API_KEY_DE_GEMINI_AQUI');
    ```
3.  **Configuración de Correo Electrónico (SMTP):**
    Configura tu cuenta emisora (se recomienda usar Gmail con una contraseña de aplicación):
    ```php
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USER', 'agendaroad@gmail.com');
    define('SMTP_PASS', 'ifcm jvbp pwaa ejtl');
    ```

---

## Cómo Integrar Clientes Nuevos

### Opción A: Cliente con Base de Datos Propia (Publicación por POST)

En este modelo, cuando el revisor aprueba el borrador, la plataforma central envía los datos del post al sitio web del cliente.

1.  Copia el archivo `ejemplo-receptor/publicar.php` al servidor del cliente (por ejemplo, en `https://misitiocliente.com/blog/publicar.php`).
2.  Abre el archivo e ingresa una API Key única en la constante `LOCAL_API_KEY`.
3.  Configura al cliente en la plataforma central usando la interfaz web (`admin/clientes/nuevo.php`):
    *   **Dominio:** `https://misitiocliente.com`
    *   **Endpoint de Publicación:** `https://misitiocliente.com/blog/publicar.php`
    *   **API Key Secreta del Sitio Receptor:** La misma clave ingresada en la constante `LOCAL_API_KEY` del cliente.
4.  ¡Listo! Al aprobarse un post, se insertará localmente en el sitio del cliente.

### Opción B: Cliente sin Base de Datos Propia (Lectura Dinámica por GET)

Ideal para clientes que quieren una solución simple y sin bases de datos adicionales. El blog del cliente consulta dinámicamente nuestra plataforma.

1.  Copia el archivo `ejemplo-receptor/blog.php` al servidor del cliente (por ejemplo, renombrado como `index.php` o `blog.php` en su servidor).
2.  Abre el archivo y configura el slug de tu cliente:
    ```php
    $clienteSlug = 'slug-del-cliente'; // Ej: adri-hair-style
    ```
3.  Asegúrate de que los estilos visuales (colores y fuentes) del sitio correspondan con lo deseado.
4.  ¡Listo! La página consultará la API GET central de la plataforma (`/platform/api/posts.php?cliente=slug`) y pintará los posts aprobados en tiempo real.

---

## Flujo Operativo Completo

1.  **Alta del Cliente:** Agrega al cliente e introduce sus datos visuales (paleta de colores, fuentes, autor).
2.  **Generación:** Entra a **Generar Post**, elige al cliente, introduce el tema del post y haz clic en **Generar**. Gemini creará el texto estructurado en JSON y el banner horizontal optimizado.
3.  **Notificación:** Haz clic en **Notificar a [Autor]**. El revisor recibirá un correo electrónico con estilos corporativos y el botón de revisión.
4.  **Revisión:** Al abrir el link del correo, el revisor verá la vista previa del post renderizado exactamente con su marca.
    *   Si presiona **Aprobar**, el post se envía a su sitio. Si la conexión falla, podrá reintentar la publicación directamente.
    *   Si presiona **Rechazar**, debe introducir el comentario de rechazo.
5.  **Regeneración:** En el listado general de posts de la administración, los posts en estado "Rechazado" habilitan un botón **Regenerar**. Al presionarlo, el sistema elimina el banner anterior, consulta de nuevo a la IA y notifica otra vez al revisor con el borrador actualizado.
