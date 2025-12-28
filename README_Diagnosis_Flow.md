# Diagnosis Flow (Frontend + Backend)

This document describes how the AI diagnosis chat works across the frontend (Vue) and backend (WordPress/PHP).

## Frontend (Vue)

- **Entry route:** `/diagnosis` renders `src/views/Diagnosis.vue`, which mounts `src/components/ChatDiagnosis.vue`.
- **Component:** `ChatDiagnosis.vue`
  - Maintains `messages` array (`role`: user/assistant).
  - Sends user input via `POST /wp-admin/admin-ajax.php` with `action=chat_diagnosis_ajax`, `message`, `conversation_history` (JSON), and `locale`.
  - Renders assistant replies and shows completion UI when the backend flags diagnosis completion.
  - Uses Toast and SweetAlert for notifications; RTL-aware layout.
  - Sanitizes/rendering: basic markdown to HTML; server also sanitizes Arabic text.

## Backend (WordPress/PHP)

- **AJAX endpoint:** `chat_diagnosis_ajax` handled in `functions/ai-integration.php` (`SNKS_AI_Integration::chat_diagnosis_ajax`).
- **Core flow:** `process_chat_diagnosis( $message, $conversation_history )`
  - Loads settings:
    - API key: `snks_ai_chatgpt_api_key`
    - Model: `snks_ai_chatgpt_model` (used directly in the OpenAI request)
    - Prompt: `snks_get_ai_chatgpt_prompt()` (default/custom from settings)
  - Two phases remain, but without question limits:
    - **Interview phase:** Conversational, no `response_format`. Temperature fixed at **0.4**.
    - **Final phase:** JSON-only (`response_format: json_object`), temperature **0.4**. Triggered when ChatGPT signals readiness (`[READY_FOR_DIAGNOSIS]`) or sufficient info is detected server-side.
  - Messages sent to OpenAI:
    - System prompt (sanitized, placeholders filled)
    - Recent conversation history (sanitized)
    - Current user message (sanitized)
  - Sanitization:
    - Removes Arabic diacritics, bidi chars, tatweel, newlines/returns, extra whitespace.
    - Ensures cleaner input to the model.
  - Temperature: hard-coded to **0.4** (interview and final).
  - No max tokens, min/max questions, or temperature settings are read from options anymore.

## Data and Results

- On final phase success, backend returns structured diagnosis data; frontend marks `diagnosisCompleted` and redirects to results.
- Diagnosis results are stored against the user (meta keys like `ai_diagnosis_result`), and history is maintained.

## Admin Settings (ChatGPT)

- Located in **ChatGPT Integration** page (`functions/admin/ai-admin-enhanced.php`).
- Remaining configurable:
  - API key
  - Model
  - Prompt source (default/custom) and custom prompt text
- Removed settings: max tokens, temperature, min/max questions (now fixed in code).

## Notes

- WordPress 6.9 KSES: form elements and SVGs are whitelisted via `wp_kses_allowed_html` filter in `anony-shrinks.php` to avoid stripping inputs/checkboxes in shortcodes.
- Temperature fixed at 0.4 to match the simplified API test flow.
- The readiness marker `[READY_FOR_DIAGNOSIS]` can be emitted by the model to jump to final JSON response; otherwise, server decides based on collected info.***

