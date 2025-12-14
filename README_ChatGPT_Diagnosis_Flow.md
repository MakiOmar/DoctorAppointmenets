# ChatGPT Diagnosis Flow Documentation

## Overview

The ChatGPT diagnosis flow is an AI-powered conversational system that helps patients identify potential mental health conditions through an interactive chat interface. The system uses OpenAI's ChatGPT API to conduct a structured conversation, ask relevant questions, and ultimately provide a diagnosis recommendation from a predefined list of available diagnoses.

## Architecture

The system consists of two main components:
1. **Frontend**: Vue.js component (`ChatDiagnosis.vue`) that handles user interaction
2. **Backend**: PHP class (`SNKS_AI_Integration`) that processes requests and communicates with OpenAI API

---

## Flow Diagram

```
User Input → Frontend (Vue) → AJAX Request → Backend (PHP) → OpenAI API → Response Processing → Frontend Display
```

---

## Frontend Flow

### File: `jalsah-ai-frontend/src/components/ChatDiagnosis.vue`

#### 1. Component Initialization
- **Lines 382-386**: Component mounts and adds welcome message
- **Lines 214-221**: `addWelcomeMessage()` function adds initial assistant greeting

#### 2. User Message Submission
- **Lines 302-378**: `sendMessage()` function handles user input
  - **Lines 308-312**: Adds user message to conversation array
  - **Lines 321-325**: Prepares form data with:
    - `action`: `'chat_diagnosis_ajax'`
    - `message`: User's input text
    - `conversation_history`: JSON stringified conversation array
    - `locale`: Current locale (en/ar)
  - **Lines 327-331**: Sends POST request to `/wp-admin/admin-ajax.php`

#### 3. Response Handling
- **Lines 333-359**: Processes successful response
  - **Lines 338-342**: Adds assistant response to messages array
  - **Lines 345-359**: If diagnosis is complete:
    - Sets diagnosis result data
    - Marks diagnosis as completed
    - Auto-redirects to results page after 3 seconds

#### 4. Question Counter
- **Lines 198-200**: `aiQuestionsCount` computed property counts assistant questions
- **Lines 202-212**: `isQuestion()` helper function detects questions by:
  - Question marks (`?`, `؟`)
  - Question words (English and Arabic)

#### 5. Previous Diagnosis Loading
- **Lines 238-285**: `loadLatestDiagnosis()` function:
  - Checks for recent diagnosis (within last hour)
  - Loads conversation history if available
  - Restores diagnosis result if found

---

## Backend Flow

### File: `functions/ai-integration.php`

#### 1. AJAX Handler Registration
- **Lines 102-103**: Registers AJAX action hooks:
  ```php
  add_action( 'wp_ajax_chat_diagnosis_ajax', array( $this, 'chat_diagnosis_ajax' ) );
  add_action( 'wp_ajax_nopriv_chat_diagnosis_ajax', array( $this, 'chat_diagnosis_ajax' ) );
  ```

#### 2. Main AJAX Handler
- **Lines 1638-1672**: `chat_diagnosis_ajax()` function:
  - **Line 1640**: Verifies JWT authentication
  - **Lines 1648-1657**: Extracts and sanitizes:
    - User message
    - Conversation history (JSON decoded)
  - **Lines 1660-1662**: Validates required fields
  - **Line 1665**: Calls `process_chat_diagnosis()` to handle the actual processing
  - **Line 1671**: Returns JSON success response

#### 3. Core Processing Function
- **Lines 4643-4976**: `process_chat_diagnosis()` - Main diagnosis processing logic

##### 3.1 Configuration Loading
- **Lines 4645-4651**: Loads OpenAI settings:
  - API key: `snks_ai_chatgpt_api_key`
  - Model: `snks_ai_chatgpt_model` (default: `gpt-3.5-turbo`)
  - System prompt: `snks_get_ai_chatgpt_prompt()` (lines 39-51)
  - Max tokens: `snks_ai_chatgpt_max_tokens` (default: 1000)
  - Temperature: `snks_ai_chatgpt_temperature` (default: 0.7)
  - Min questions: `snks_ai_chatgpt_min_questions` (default: 5)
  - Max questions: `snks_ai_chatgpt_max_questions` (default: 10)

##### 3.2 Language Detection
- **Lines 4658-4660**: Determines conversation language:
  - Uses `detect_language()` helper (lines 637-646)
  - Checks for Arabic characters using regex pattern
  - Falls back to locale parameter

##### 3.3 Diagnosis List Preparation
- **Lines 4663-4674**: Fetches available diagnoses from database:
  - Queries `wp_snks_diagnoses` table
  - Formats diagnosis names based on language (Arabic/English)
  - Creates list for system prompt

##### 3.4 Question Counting
- **Lines 4676-4682**: Counts questions already asked:
  - Iterates through conversation history
  - Uses `is_question()` helper (lines 651-677) to identify questions
  - Tracks count for validation

##### 3.5 System Prompt Construction
- **Lines 4687-4720**: Builds enhanced system prompt:
  - **Lines 4688-4690**: Adds language instruction (Arabic/English)
  - **Lines 4692-4714**: Adds question limit instructions:
    - Minimum questions required
    - Maximum questions allowed
    - Current question count
    - Warnings if limits reached
  - **Line 4718**: Adds available diagnoses list
  - **Line 4720**: Adds conversation rules and JSON response format requirements

##### 3.6 Message Array Construction
- **Lines 4722-4742**: Builds messages array for OpenAI API:
  - **Lines 4722-4725**: Adds system prompt
  - **Lines 4727-4736**: Adds recent conversation history (last 10 messages)
  - **Lines 4738-4742**: Adds current user message

##### 3.7 Maximum Questions Check
- **Lines 4744-4757**: If max questions reached:
  - Forces completion without API call
  - Returns default "general_assessment" diagnosis
  - Skips to response processing

##### 3.8 OpenAI API Call
- **Lines 4759-4778**: Makes API request:
  - **Lines 4760-4766**: Prepares request data:
    - Model selection
    - Messages array
    - Max tokens
    - Temperature
    - **Forced JSON response format** (`response_format: { type: 'json_object' }`)
  - **Lines 4768-4778**: Sends POST request to `https://api.openai.com/v1/chat/completions`
  - **Lines 4780-4791**: Handles response and extracts JSON content

##### 3.9 Response Processing
- **Line 4796**: `process_response:` label for goto statement
- **Lines 4798-4808**: Validates JSON response:
  - If invalid, uses `generate_contextual_fallback()` (lines 682-833)
  - Returns fallback message

##### 3.10 Question Count Validation
- **Lines 4810-4837**: Enforces question limits:
  - **Lines 4814-4821**: If status is "complete" but question count < minimum:
    - Forces status to "incomplete"
    - Adds message requiring more questions
  - **Lines 4824-4837**: If question count >= maximum:
    - Forces status to "complete"
    - Sets default diagnosis if missing
    - Provides completion message

##### 3.11 Diagnosis Validation
- **Lines 4839-4864**: Validates diagnosis against database:
  - Checks if diagnosis name matches available diagnoses
  - Supports both Arabic and English name matching
  - Extracts diagnosis ID, name, and description
  - Only processes if status is "complete"

##### 3.12 Response Formatting
- **Lines 4866-4911**: Formats final response message:
  - **Lines 4868-4895**: Adds confidence level text (high/medium/low)
  - **Lines 4897-4911**: Builds completion message with:
    - Diagnosis name
    - Confidence level
    - Reasoning from AI
    - Diagnosis description
    - Next steps message

##### 3.13 Diagnosis Storage
- **Lines 4913-4945**: Saves diagnosis result:
  - **Lines 4916-4925**: Creates diagnosis data array:
    - Diagnosis ID, name, description
    - Confidence level
    - Reasoning
    - Conversation history
    - Language
    - Completion timestamp
  - **Line 4928**: Stores in user meta: `ai_diagnosis_result`
  - **Lines 4931-4944**: Maintains diagnosis history (last 10 results)

##### 3.14 Return Response
- **Lines 4947-4975**: Returns formatted response:
  - **Lines 4947-4957**: If diagnosis complete:
    - Returns message and diagnosis details
  - **Lines 4958-4975**: If diagnosis incomplete:
    - Returns reasoning or fallback message
    - Marks as incomplete

---

## Helper Functions

### Language Detection
- **File**: `functions/ai-integration.php`
- **Lines 637-646**: `detect_language()`
  - Uses regex pattern to detect Arabic characters
  - Returns `'arabic'` or `'english'`

### Question Detection
- **File**: `functions/ai-integration.php`
- **Lines 651-677**: `is_question()`
  - Checks for question marks (`?`, `؟`)
  - Checks for Arabic question words: `هل`, `متى`, `أين`, `كيف`, `لماذا`, `من`, `ما`, `أي`
  - Checks for English question words: `what`, `when`, `where`, `how`, `why`, `who`, `which`, `do`, `does`, `did`, `can`, `could`, `would`, `will`

### Contextual Fallback
- **File**: `functions/ai-integration.php`
- **Lines 682-833**: `generate_contextual_fallback()`
  - Generates fallback responses when AI response is invalid
  - Analyzes conversation history
  - Provides contextual follow-up questions
  - Supports both Arabic and English

### Default Prompt
- **File**: `functions/ai-integration.php`
- **Lines 21-36**: `snks_get_ai_chatgpt_default_prompt()`
  - Returns default system prompt for ChatGPT
  - Defines AI assistant role and behavior

### Prompt Retrieval
- **File**: `functions/ai-integration.php`
- **Lines 39-51**: `snks_get_ai_chatgpt_prompt()`
  - Retrieves system prompt (custom or default)
  - Checks option: `snks_ai_chatgpt_use_default_prompt`

---

## Database Tables

### `wp_snks_diagnoses`
- Stores available diagnoses
- Fields: `id`, `name`, `name_en`, `name_ar`, `description`, `description_en`, `description_ar`
- Referenced at: **Line 4664**

### User Meta
- `ai_diagnosis_result`: Current diagnosis result (line 4928)
- `ai_diagnosis_history`: Array of past diagnosis results (line 4931)

---

## Configuration Options

All settings are stored as WordPress options:

| Option Name | Default | Description | Line Reference |
|------------|---------|-------------|----------------|
| `snks_ai_chatgpt_api_key` | - | OpenAI API key | 4645 |
| `snks_ai_chatgpt_model` | `gpt-3.5-turbo` | OpenAI model | 4646 |
| `snks_ai_chatgpt_prompt` | Default prompt | Custom system prompt | 4647 |
| `snks_ai_chatgpt_max_tokens` | `1000` | Maximum tokens in response | 4648 |
| `snks_ai_chatgpt_temperature` | `0.7` | Response creativity (0-1) | 4649 |
| `snks_ai_chatgpt_min_questions` | `5` | Minimum questions before diagnosis | 4650 |
| `snks_ai_chatgpt_max_questions` | `10` | Maximum questions allowed | 4651 |
| `snks_ai_chatgpt_use_default_prompt` | `'0'` | Use default prompt flag | 42 |

---

## Response Format

### OpenAI API Response Structure
The system enforces JSON response format with this structure:
```json
{
  "diagnosis": "diagnosis_name_from_list",
  "confidence": "low|medium|high",
  "reasoning": "conversational response to patient",
  "status": "complete|incomplete",
  "question_count": 6
}
```

### Backend Response Structure
```json
{
  "success": true,
  "data": {
    "message": "Assistant's response text",
    "diagnosis": {
      "completed": true|false,
      "id": 123,
      "title": "Diagnosis Name",
      "description": "Diagnosis description",
      "confidence": "high|medium|low",
      "reasoning": "AI reasoning"
    }
  }
}
```

---

## Error Handling

### Authentication Errors
- **Line 1640**: JWT token verification
- **Line 1642**: Returns 401 error if authentication fails

### Validation Errors
- **Line 1660**: Checks for empty message
- **Line 1661**: Returns 400 error if validation fails

### API Errors
- **Line 4780**: Checks for WordPress error in API response
- **Line 4781**: Returns WP_Error with API error message
- **Line 4787**: Checks for invalid response structure
- **Line 4788**: Returns WP_Error for invalid response

### Fallback Handling
- **Line 4798**: If JSON parsing fails, uses fallback
- **Line 4800**: Calls `generate_contextual_fallback()` for graceful degradation

---

## Question Limit Enforcement

The system strictly enforces question limits:

1. **Minimum Questions** (default: 5)
   - **Line 4700-4702**: Warns AI if minimum not reached
   - **Line 4814-4821**: Forces incomplete status if diagnosis attempted too early

2. **Maximum Questions** (default: 10)
   - **Line 4698-4699**: Warns AI if maximum reached
   - **Line 4745-4757**: Forces completion if maximum reached
   - **Line 4824-4837**: Forces completion if exceeded

3. **Question Counting**
   - **Lines 4676-4682**: Counts questions in conversation history
   - **Line 4811**: Validates question count from AI response

---

## Language Support

### Bilingual Support
- **Arabic**: Full support with Modern Standard Arabic (الفصحى)
- **English**: Full support
- **Detection**: Automatic based on user input (lines 4658-4660)
- **Response**: Matches user's language preference

### Language-Specific Features
- **Lines 4668-4672**: Diagnosis names in appropriate language
- **Lines 4688-4690**: Language-specific instructions
- **Lines 4897-4911**: Language-specific response formatting

---

## Security Considerations

1. **Authentication**: JWT token verification (line 1640)
2. **Input Sanitization**: 
   - `sanitize_textarea_field()` for messages (line 1648)
   - JSON decoding with validation (lines 1651-1657)
3. **CORS Handling**: Configured for allowed origins (lines 85, 1755-1760)
4. **Nonce Verification**: WordPress nonce system for AJAX requests

---

## Key Features

1. **Structured Conversation**: Enforces minimum/maximum question limits
2. **JSON-Enforced Responses**: Guarantees machine-readable output
3. **Diagnosis Validation**: Only allows diagnoses from database
4. **Conversation History**: Maintains context across messages
5. **Bilingual Support**: Full Arabic and English support
6. **Graceful Fallbacks**: Handles API errors gracefully
7. **User Data Persistence**: Saves diagnosis results and history

---

## Testing

### AJAX Endpoint
- URL: `/wp-admin/admin-ajax.php`
- Action: `chat_diagnosis_ajax`
- Method: POST
- Required Parameters:
  - `message`: User's message
  - `conversation_history`: JSON array of previous messages
  - `locale`: Language locale (en/ar)

### Test Endpoints
- **Line 100**: `test_diagnosis_ajax` - Test diagnosis endpoint
- **Line 110**: `test_diagnosis_limit_ajax` - Test question limits

---

## Admin Configuration

ChatGPT settings can be configured in WordPress admin:
- **File**: `functions/admin/ai-admin-enhanced.php`
- **Lines 2383-2530**: ChatGPT integration settings page
- **Lines 2387-2401**: Settings update handler

---

## Related Files

1. **Frontend Component**: `jalsah-ai-frontend/src/components/ChatDiagnosis.vue`
2. **Backend Integration**: `functions/ai-integration.php`
3. **Admin Settings**: `functions/admin/ai-admin-enhanced.php`
4. **Database Tables**: `includes/ai-tables.php`
5. **Guidelines**: `Patient_Diagnosis_Assistant_Guidelines.md`

---

## Notes

- The system uses `response_format: { type: 'json_object' }` to force JSON responses from OpenAI
- Conversation history is limited to last 10 messages to avoid token limits (line 4728)
- Diagnosis results are stored in user meta for persistence
- The system automatically redirects to results page after completion (frontend line 352-358)
- Question counting is strict and enforced at multiple levels to ensure compliance
