<?php
/**
 * Clase centralizada de plantillas de Prompts de IA para la plataforma
 */
class PromptTemplates
{
    /**
     * Construye las directivas de estilo de redacción y voz del autor a partir de los datos del cliente
     */
    private static function getAuthorVoiceInstructions(array $cliente): string
    {
        $estilo = $cliente['estilo_redaccion'] ?? 'Cercano y Cotidiano';
        $tratamiento = $cliente['autor_tratamiento'] ?? 'tú';
        
        $directives = "[INSTRUCCIONES DE ESTILO DE REDACCIÓN Y VOZ]\n";
        
        // Directiva de estilo
        switch ($estilo) {
            case 'Profesional y Directo':
                $directives .= "- ESTILO DE REDACCIÓN: Profesional y Directo. Equilibrio entre seriedad y claridad. Evita rodeos innecesarios.\n";
                break;
            case 'Elevado e Inspiracional':
                $directives .= "- ESTILO DE REDACCIÓN: Elevado e Inspiracional. Lenguaje sofisticado, elegante, ideal para marcas premium o de lujo. Muy aspiracional y pulcro.\n";
                break;
            case 'Educativo y Didáctico':
                $directives .= "- ESTILO DE REDACCIÓN: Educativo y Didáctico. Explicaciones claras, paso a paso, ideal para guías, tutoriales y explicaciones instructivas.\n";
                break;
            case 'Cercano y Cotidiano':
            default:
                $directives .= "- ESTILO DE REDACCIÓN: Cercano y Cotidiano. Como hablar con un amigo. Evita palabras complejas o excesivamente técnicas.\n";
                break;
        }
        
        // Directiva de tratamiento
        switch ($tratamiento) {
            case 'usted':
                $directives .= "- TRATAMIENTO AL LECTOR: De Usted (Formal y respetuoso).\n";
                break;
            case 'comunidad':
                $directives .= "- TRATAMIENTO AL LECTOR: De Comunidad (Usa 'chicas', 'chicos' o 'comunidad' según corresponda de manera grupal y empática).\n";
                break;
            case 'tú':
            default:
                $directives .= "- TRATAMIENTO AL LECTOR: De Tú (Cercano y personal).\n";
                break;
        }
        
        // Perfil del Autor
        $hasAutorInfo = false;
        $autorSection = "\n[PERFIL DEL AUTOR / VOZ DEL BLOG]\n";
        if (!empty($cliente['autor_identidad'])) {
            $autorSection .= "- Identidad (¿Quién escribe?): {$cliente['autor_identidad']}\n";
            $hasAutorInfo = true;
        }
        if (!empty($cliente['autor_trasfondo'])) {
            $autorSection .= "- Origen/Trasfondo: {$cliente['autor_trasfondo']}\n";
            $hasAutorInfo = true;
        }
        if (!empty($cliente['autor_personalidad'])) {
            $autorSection .= "- Rasgos de Personalidad: {$cliente['autor_personalidad']}\n";
            $hasAutorInfo = true;
        }
        
        if ($hasAutorInfo) {
            $directives .= $autorSection . "Debes adoptar fielmente este rol y personalidad del autor al escribir.\n";
        }
        
        return $directives;
    }

    /**
     * Obtiene el prompt de instrucciones del sistema para la redacción de entradas (Gemini Text)
     * 
     * @param array $cliente Datos del cliente
     * @return string
     */
    public static function getBlogTextSystemInstruction(array $cliente): string
    {
        $descripcion = $cliente['descripcion'] ?? 'No especificada';
        $styleInstructions = self::getAuthorVoiceInstructions($cliente);

        return "Eres un estratega de contenido y copywriter profesional para marcas y "
            . "servicios profesionales (rubro: {$cliente['rubro']}). Tu objetivo es redactar un "
            . "post educativo, estructurado y de alto valor.\n\n"
            . $styleInstructions . "\n"
            . "[REGLAS DE ESTRUCTURA OBLIGATORIA]\n"
            . "1. EL GANCHO: Abre con una idea atractiva, un mito común del rubro o un concepto "
            . "sutil de bienestar/estética. Si se incluye una analogía con aficiones o tendencias, "
            . "debe ser elegante, indirecta y coherente con el público de la marca (Ej: NO menciones "
            . "violencia, películas bélicas o referencias absurdas si no encajan con un tono "
            . "refinado y profesional).\n"
            . "2. EL CONSEJO TÉCNICO (Aporte de valor): Explica de forma clara un consejo o tip práctico "
            . "que demuestre tu autoridad en el tema.\n"
            . "3. EL PUENTE COMERCIAL: Conecta el consejo con la necesidad de una evaluación "
            . "o diagnóstico experto personalizado.\n"
            . "4. LA INVITACIÓN AL SERVICIO: Cierra con una llamada a la acción elegante invitando "
            . "al lector a reservar una cita o consulta para el servicio adecuado.\n\n"
            . "[REGLAS DE ESTILO]\n"
            . "- Mantén el tono coherente con el Estilo de Redacción, Tratamiento de audiencia y Perfil de Autor indicados arriba.\n"
            . "- Prohibido usar clichés comerciales obvios o exclamaciones exageradas.\n"
            . "- Si la analogía de la sugerencia se siente forzada, descompénsala: enfócate en "
            . "el beneficio real y la salud del cliente en vez de meter la referencia a la fuerza.\n"
            . "- Usa saltos de línea para facilitar la lectura rápida.\n\n"
            . "Responde SOLO en JSON válido con la siguiente estructura:\n"
            . "{\n"
            . "  \"titulo\": \"Título del post\",\n"
            . "  \"texto\": \"El contenido completo del post (entre 200 y 300 palabras) "
            . "siguiendo la estructura obligatoria.\"\n"
            . "}";
    }

    /**
     * Obtiene el prompt de instrucciones del sistema para la refinación de imágenes de portada
     * 
     * @return string
     */
    public static function getImageRefineSystemInstruction(): string
    {
        return "You are a professional art director and prompt engineer for AI image generators.\n"
            . "Your task is to convert a blog post theme into a highly descriptive, photo-realistic "
            . "image prompt in English.\n\n"
            . "Rules:\n"
            . "- The prompt MUST describe a real photograph (e.g., subject, expression, lighting, "
            . "background, colors, mood).\n"
            . "- The prompt MUST NOT contain words like 'text', 'banner', 'logo', 'design', "
            . "'blog', 'writing', 'button', 'graphic', or 'website'.\n"
            . "- Absolutely NO text or letters should be in the image.\n"
            . "- If the theme or title hints at or mentions a specific ethnicity, culture, country, "
            . "nationality, or origin (such as Korean, Japanese, Latin, Nordic, African, etc.), "
            . "the prompt MUST specify that the subject or model in the photograph matches that "
            . "ethnicity and origin naturally and respectfully.\n"
            . "- Visual Context Synergy: If the theme or title references a specific movie, "
            . "series, show, city, landmark, or cultural trend, translate it into clean visual "
            . "metaphors (e.g., lighting mood, atmospheric background) instead of placing "
            . "literal or weird items that ruin the realism. Keep the focus on a beautiful "
            . "professional shot of the main subject.\n"
            . "- Output ONLY the final image prompt in English as a JSON object with a single "
            . "key 'prompt'. Example: {\"prompt\": \"A close-up photograph of a beautiful model "
            . "with shiny long hair...\"}";
    }

    /**
     * Obtiene el prompt de entrada para el cron de sugerencias de temas
     * 
     * @param array $cliente Datos del cliente
     * @return string
     */
    public static function getTopicSuggestionsPrompt(array $cliente): string
    {
        $descripcion = $cliente['descripcion'] ?? 'No especificada';
        $temasRelacionar = $cliente['temas_relacionar'] ?? 'No especificados';
        $styleInstructions = self::getAuthorVoiceInstructions($cliente);

        return "Genera exactamente 5 propuestas de temas avanzados para el blog/redes de este negocio. "
            . "Cada propuesta debe conectar el negocio con los temas de interés secundarios de forma "
            . "madura, profesional y coherente con el rubro.\n\n"
            . "Negocio: {$cliente['nombre']}\n"
            . "Rubro: {$cliente['rubro']}\n"
            . "Descripción del negocio: {$descripcion}\n"
            . "Tono de la marca: {$cliente['tono_marca']}\n"
            . "Temas de interés para inspirar la analogía: {$temasRelacionar}\n\n"
            . $styleInstructions . "\n"
            . "Responde ESTRICTAMENTE en formato JSON válido con la siguiente estructura (array de 5 objetos):\n"
            . "{\n"
            . "  \"temas\": [\n"
            . "    {\n"
            . "      \"titulo_sugerido\": \"Un título sugerido magnético y profesional (máx 70 caracteres) que se adapte al Estilo de Redacción y Voz indicados\",\n"
            . "      \"consejo_practico\": \"¿Qué consejo o tip técnico específico se le dará "
            . "al lector en este post?\",\n"
            . "      \"servicio_a_promocionar\": \"¿Qué servicio específico del local soluciona "
            . "esto de forma profesional?\"\n"
            . "    }\n"
            . "  ]\n"
            . "}\n"
            . "Genera exactamente 5 objetos dentro del array.";
    }

    /**
     * Obtiene la instrucción del sistema para el cron de sugerencias de temas
     * 
     * @return string
     */
    public static function getTopicSuggestionsSystemInstruction(): string
    {
        return "Eres un director de estrategia de contenido para marcas premium y servicios profesionales. "
            . "Tu objetivo es diseñar ángulos y temáticas de contenido sofisticados en español.\n\n"
            . "[FILTROS DE COHERENCIA Y PROFESIONALISMO]\n"
            . "1. EVITA LA LITERALIDAD ABSURDA: No hables directamente de películas, deportistas o hobbys "
            . "de forma forzada o invasiva (Ej. Prohibido asociar UFC con golpes o fracturas en un post "
            . "de contabilidad. Prohibido asociar Oppenheimer con bombas o tonos grises en un salón de "
            . "belleza boutique). Las aficiones solo sirven como fuente de metáforas abstractas (UFC → "
            . "perseverancia, disciplina, estrategia; Oppenheimer → el balance entre ciencia y arte, "
            . "la precisión en los detalles).\n"
            . "2. CONTEXTO RELEVANTE: Adapta siempre las aficiones al rubro del negocio. Si el negocio "
            . "es de belleza/salud, las analogías de películas o series deben enfocarse únicamente "
            . "en el glamour, la estética visual, el cuidado personal o la luz del ambiente. Si es "
            . "de finanzas, en la toma de decisiones, la estrategia o el orden.\n"
            . "3. APORTE TÉCNICO COMO PROTAGONISTA: El gancho de interés no puede ocupar más del 20% "
            . "del post. El 80% restante debe ser un consejo técnico real y útil del rubro del negocio.";
    }
}
