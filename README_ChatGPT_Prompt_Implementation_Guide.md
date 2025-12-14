# ChatGPT Prompt Implementation Guide

## Overview

This document explains the changes required to implement the new Arabic prompt system that makes ChatGPT follow specific assessment guidelines, including dialect detection, mandatory initial questions, emergency handling, and enhanced response structure.

---

## Current vs. New Requirements Comparison

### Current Implementation Issues

1. **Language Instruction**: Currently enforces Modern Standard Arabic (الفصحى) only
   - **Location**: `functions/ai-integration.php` lines 4688-4690
   - **Issue**: New prompt requires matching patient's dialect, not forcing formal Arabic

2. **Country/Region Question**: Currently explicitly prevents asking about country
   - **Location**: `functions/ai-integration.php` line 4720
   - **Issue**: New prompt requires asking about country/region early in conversation

3. **Response Format**: Missing new required fields
   - **Current Fields**: `diagnosis`, `confidence`, `reasoning`, `status`, `question_count`
   - **New Required Fields**: `ai_diagnosis`, `therapist_summary`, `patient_summary`
   - **Location**: `functions/ai-integration.php` lines 4720, 4794-4975

4. **Initial Questions**: No enforcement of mandatory initial questions
   - **Missing**: "Are you the patient or booking for someone else?"
   - **Missing**: "What is the patient's age?"
   - **Location**: No current implementation

5. **Emergency Handling**: No suicide/self-harm detection
   - **Location**: No current implementation

6. **General Assessment Types**: Limited to single `general_assessment`
   - **Current**: Only `general_assessment` (line 4827)
   - **New Required**: `general_assessment_pediatric`, `general_assessment_doctor`, `general_assessment_therapist`
   - **Location**: `functions/ai-integration.php` lines 4824-4837

7. **Default Prompt**: English-focused default prompt
   - **Location**: `functions/ai-integration.php` lines 21-36
   - **Issue**: New prompt is Arabic-first with specific assessment guidelines

---

## Required Changes

### 1. Update Default System Prompt

**File**: `functions/ai-integration.php`  
**Lines**: 21-36

**Current Code**:
```php
function snks_get_ai_chatgpt_default_prompt() {
    return "You are a compassionate and professional mental health AI assistant...";
}
```

**Required Change**: Replace with the new Arabic prompt provided by the user. The prompt should be stored as the default and include all the assessment guidelines.

**Implementation**:
```php
function snks_get_ai_chatgpt_default_prompt() {
    return "قم بدور مساعد تقييم نفسي مبدئي للمستخدمين الجدد..."; // Full prompt from user
}
```

---

### 2. Modify Language Instruction Logic

**File**: `functions/ai-integration.php`  
**Lines**: 4687-4690

**Current Code**:
```php
$language_instruction = $is_arabic ?
    'IMPORTANT: Respond ONLY in Modern Standard Arabic (الفصحى)...' :
    'IMPORTANT: Respond ONLY in English language...';
```

**Required Change**: 
- Remove the formal Arabic requirement
- Add dialect detection instruction
- Add language requirement notice for non-Arabic speakers

**New Code**:
```php
// Detect dialect from conversation history
$detected_dialect = 'egyptian'; // default
foreach ( $conversation_history as $msg ) {
    if ( $msg['role'] === 'user' ) {
        $detected_dialect = $this->detect_country_and_dialect( $msg['content'] );
        if ( $detected_dialect !== 'egyptian' ) {
            break;
        }
    }
}

$language_instruction = $is_arabic ?
    "IMPORTANT: من بداية المحادثة يجب ملاحظة اللهجة العربية التي يتحدث بها المريض والتحدث معه بنفس اللهجة. اللهجة المكتشفة: {$detected_dialect}. استخدم نفس اللهجة في جميع ردودك." :
    "IMPORTANT: Respond in the same language as the patient. However, inform them at the beginning that sessions on the website are only available in Arabic.";
```

**Note**: The `detect_country_and_dialect()` function already exists at line 866 and supports multiple dialects.

---

### 3. Update System Prompt Construction

**File**: `functions/ai-integration.php`  
**Lines**: 4716-4720

**Current Code**: Builds prompt with English rules and explicitly prevents asking about country.

**Required Changes**:

1. **Remove Country Restriction**: Remove the line that says "DO NOT ask about the patient's country or region"
2. **Add Mandatory Initial Questions**: Add instructions for asking:
   - "Are you the patient or booking for someone else?" (at start)
   - "What is the patient's age?" (at start)
   - "What is your country/region?" (early in conversation)

3. **Add Emergency Handling**: Add instructions for detecting and handling suicidal thoughts

4. **Update Response Format**: Update JSON structure to include new fields

**New Prompt Section** (to be added after line 4718):
```php
$mandatory_questions_instruction = "
## أسئلة إلزامية في البداية:
- في بداية المحادثة يجب سؤال الشخص: هل أنت المريض أم تحجز لشخص آخر؟
- في بداية المحادثة يتم السؤال عن سن المريض
- اطلب من المستخدم ذكر بلده/منطقته ضمن أوائل الأسئلة للحصول على سياق ثقافي

## التعامل مع حالات الطوارئ:
- في حالة تحدث المريض عن أفكار انتحارية جادة أو خطط انتحارية أو أفكار وخطط لإيذاء النفس أو الغير، يتم إخبار المريض أن حالته حالة طارئة وأننا غير قادرين على مساعدته وأنه عليه فوراً إخبار أحد الأشخاص المقربين له والتوجه فوراً لقسم الطوارئ في أقرب مستشفى نفسي له.
";

$conversation_flow_instruction = "
## قواعد المحادثة:
- يجب أن تكون المحادثة بشكل انسيابي وليس في شكل سؤال وجواب فقط
- إذا خرج المستخدم عن الموضوع، ذكّره: \"خلينا نركّز مع بعض علشان نقدر نوصّل لتقييم نفسي مبدئي واضح ونرشّحلك معالج مناسب.\"
- التشخيصات في الطب النفسي كثيراً ما تكون متداخلة، يجب استثناء كل التشخيصات الممكنة ولكن في أقل عدد من الأسئلة حتى لا نطيل على المريض
- في النهاية يتم اعتماد التشخيص الأساسي الذي يسبب المشكلة الأكثر حدة للمريض أو المشكلة الأساسية له
";
```

---

### 4. Update Response Format in System Prompt

**File**: `functions/ai-integration.php`  
**Line**: 4720 (in the response format section)

**Current JSON Structure**:
```json
{
  "diagnosis": "diagnosis_name_from_list",
  "confidence": "low|medium|high",
  "reasoning": "your conversational response",
  "status": "complete|incomplete",
  "question_count": 6
}
```

**New Required JSON Structure**:
```json
{
  "ai_diagnosis": "التشخيص العلمي الخاص بالذكاء الاصطناعي",
  "diagnosis": "diagnosis_name_from_list",
  "reasoning": "هدف المحادثة مع المريض هنا",
  "status": "complete|incomplete",
  "question_count": 6,
  "therapist_summary": "ملخص رسمي للمعالج",
  "patient_summary": "ملخص رسمي للمريض"
}
```

**Changes**:
- Remove `confidence` field (not in new format)
- Add `ai_diagnosis` field (scientific diagnosis name written by AI)
- Add `therapist_summary` field
- Add `patient_summary` field

**Implementation** (update line 4720):
```php
$response_format_instruction = "
RESPONSE FORMAT:
أرجع JSON بهذا الشكل فقط:
{
  \"ai_diagnosis\": \"التشخيص العلمي الخاص بالذكاء الاصطناعي\",
  \"diagnosis\": \"diagnosis_name_from_list\",
  \"reasoning\": \"هدف المحادثة مع المريض هنا\",
  \"status\": \"complete|incomplete\",
  \"question_count\": " . ( $ai_questions_count + 1 ) . ",
  \"therapist_summary\": \"ملخص رسمي للمعالج\",
  \"patient_summary\": \"ملخص رسمي للمريض\"
}

- عند status = \"incomplete\": diagnosis و ai_diagnosis يمكن تركهما فارغين
- عند status = \"complete\": املأ جميع الحقول مع مراعاة القواعد أعلاه
- قم أنت دائماً بكتابة التشخيص للمريض بنفسك حسب الاسم العلمي للتشخيص ولا تستخدم التشخيصات الموجودة في \"available_diagnoses\" لأنها خاصة ببرمجة الموقع فقط
- قم بمقارنة التشخيص الذي وصلت له مع التشخيصات الموجودة في \"available_diagnoses\" واختر الأقرب لها
";
```

---

### 5. Update General Assessment Logic

**File**: `functions/ai-integration.php`  
**Lines**: 4824-4837

**Current Code**: Uses single `general_assessment` for all cases.

**Required Change**: Implement logic to determine which general assessment type based on:
- Patient age (≤12 years = pediatric)
- Patient age (>12 years) + needs medication = doctor
- Patient age (>12 years) + needs therapy (not medication) = therapist

**New Code**:
```php
// If question count exceeds maximum, force complete
if ( $new_question_count >= $max_questions && $response_data['status'] !== 'complete' ) {
    $response_data['status'] = 'complete';
    
    // Determine patient age from conversation history
    $patient_age = $this->extract_patient_age( $conversation_history );
    $needs_medication = $this->detect_medication_need( $conversation_history );
    
    if ( empty( $response_data['diagnosis'] ) ) {
        // Determine appropriate general assessment type
        if ( $patient_age !== null && $patient_age <= 12 ) {
            $response_data['diagnosis'] = 'general_assessment_pediatric';
        } elseif ( $patient_age !== null && $patient_age > 12 ) {
            if ( $needs_medication ) {
                $response_data['diagnosis'] = 'general_assessment_doctor';
            } else {
                $response_data['diagnosis'] = 'general_assessment_therapist';
            }
        } else {
            // Fallback if age not determined
            $response_data['diagnosis'] = 'general_assessment_therapist';
        }
    }
    
    // Remove confidence field (not in new format)
    // Set appropriate reasoning message
    if ( $is_arabic ) {
        $response_data['reasoning'] = 'بناءً على محادثتنا، سأقوم بإحالتك لتقييم نفسي عام مع معالج متخصص.';
    } else {
        $response_data['reasoning'] = 'Based on our conversation, I will refer you to a general psychological assessment with a specialized therapist.';
    }
}
```

**New Helper Functions Needed**:
```php
/**
 * Extract patient age from conversation history
 */
private function extract_patient_age( $conversation_history ) {
    // Look for age mentions in conversation
    foreach ( $conversation_history as $msg ) {
        if ( $msg['role'] === 'user' ) {
            // Try to extract age number
            if ( preg_match( '/\b(\d{1,2})\s*(?:سنة|year|years|عام|age)\b/i', $msg['content'], $matches ) ) {
                return intval( $matches[1] );
            }
            // Also check for common age patterns
            if ( preg_match( '/\b(?:عمر|age)\s*(?:هو|is|)\s*(\d{1,2})\b/i', $msg['content'], $matches ) ) {
                return intval( $matches[1] );
            }
        }
    }
    return null;
}

/**
 * Detect if patient needs medication based on conversation
 */
private function detect_medication_need( $conversation_history ) {
    $medication_keywords = array(
        'دواء', 'medication', 'drug', 'prescription', 'psychotic', 'ذهاني', 
        'hallucination', 'هلاوس', 'delusion', 'وهام', 'severe', 'شديد'
    );
    
    foreach ( $conversation_history as $msg ) {
        $content_lower = strtolower( $msg['content'] );
        foreach ( $medication_keywords as $keyword ) {
            if ( stripos( $content_lower, $keyword ) !== false ) {
                return true;
            }
        }
    }
    return false;
}
```

---

### 6. Update Response Parsing and Validation

**File**: `functions/ai-integration.php`  
**Lines**: 4794-4975

**Required Changes**:

1. **Remove Confidence Handling**: Remove all confidence-related code (lines 4868-4895)

2. **Add New Field Parsing**: Parse and validate new fields:
   - `ai_diagnosis` (scientific diagnosis)
   - `therapist_summary`
   - `patient_summary`

3. **Update Diagnosis Storage**: Store new fields in user meta

**New Code** (around line 4916):
```php
$diagnosis_data = array(
    'diagnosis_id'          => $diagnosis_id,
    'diagnosis_name'        => $diagnosis_name,
    'diagnosis_description' => $diagnosis_description,
    'ai_diagnosis'          => $response_data['ai_diagnosis'] ?? '', // NEW
    'reasoning'             => $response_data['reasoning'] ?? '',
    'therapist_summary'     => $response_data['therapist_summary'] ?? '', // NEW
    'patient_summary'       => $response_data['patient_summary'] ?? '', // NEW
    'conversation_history'  => $conversation_history,
    'language'              => $locale,
    'completed_at'          => current_time( 'mysql' ),
);
```

4. **Update Response Return**: Include new fields in response (around line 4947):
```php
return array(
    'message'   => $message,
    'diagnosis' => array(
        'completed'        => true,
        'id'               => $diagnosis_id,
        'title'            => $diagnosis_name,
        'description'      => $diagnosis_description,
        'ai_diagnosis'     => $response_data['ai_diagnosis'] ?? '', // NEW
        'reasoning'        => $response_data['reasoning'] ?? '',
        'therapist_summary' => $response_data['therapist_summary'] ?? '', // NEW
        'patient_summary'  => $response_data['patient_summary'] ?? '', // NEW
    ),
);
```

---

### 7. Add Emergency Detection and Handling

**File**: `functions/ai-integration.php`  
**Location**: Add new method and integrate into `process_chat_diagnosis()`

**New Method**:
```php
/**
 * Detect emergency situations (suicidal thoughts, self-harm)
 */
private function detect_emergency_situation( $message, $conversation_history ) {
    $emergency_keywords_ar = array(
        'انتحار', 'انتحاري', 'أقتل نفسي', 'أريد أن أموت', 'لا أريد أن أعيش',
        'أفكر في الانتحار', 'خطط انتحارية', 'إيذاء النفس', 'أذى نفسي',
        'أذى الآخرين', 'أقتل', 'أموت'
    );
    
    $emergency_keywords_en = array(
        'suicide', 'suicidal', 'kill myself', 'want to die', "don't want to live",
        'thinking about suicide', 'suicide plan', 'self harm', 'hurt myself',
        'hurt others', 'kill', 'die'
    );
    
    $all_keywords = array_merge( $emergency_keywords_ar, $emergency_keywords_en );
    $message_lower = strtolower( $message );
    
    foreach ( $all_keywords as $keyword ) {
        if ( stripos( $message_lower, $keyword ) !== false ) {
            return true;
        }
    }
    
    // Also check conversation history
    foreach ( $conversation_history as $msg ) {
        if ( $msg['role'] === 'user' ) {
            $content_lower = strtolower( $msg['content'] );
            foreach ( $all_keywords as $keyword ) {
                if ( stripos( $content_lower, $keyword ) !== false ) {
                    return true;
                }
            }
        }
    }
    
    return false;
}
```

**Integration** (add at beginning of `process_chat_diagnosis()`, after line 4660):
```php
// Check for emergency situations
if ( $this->detect_emergency_situation( $message, $conversation_history ) ) {
    $emergency_message = $is_arabic ?
        "حالتك حالة طارئة ونحن غير قادرين على مساعدتك من خلال هذا النظام. يرجى إخبار أحد الأشخاص المقربين لك فوراً والتوجه فوراً لقسم الطوارئ في أقرب مستشفى نفسي لك." :
        "Your situation is an emergency and we cannot help you through this system. Please immediately inform a close person and go to the emergency department of the nearest psychiatric hospital.";
    
    return array(
        'message'   => $emergency_message,
        'diagnosis' => array(
            'completed' => false,
            'emergency' => true,
        ),
    );
}
```

---

### 8. Update Available Diagnoses List Format

**File**: `functions/ai-integration.php`  
**Lines**: 4663-4674, 4718

**Current**: Simple list with names and IDs

**Required**: Add detailed descriptions for each diagnosis type as specified in the new prompt, including when to use each diagnosis.

**New Code** (update line 4718):
```php
$available_diagnoses_text = "
## جميع تشخيصات \"available_diagnoses\" ومتى يتم اختيارها:

pediatric - جميع التشخيصات الخاصة بالأطفال في سن 12 سنة أو أقل

geriatric - الاضطرابات النفسية الخاصة بالمسنين مثل الزهايمر والديمينشيا بأنواعها...

Gender_Dysphoria - اضطراب الهوية الجندرية...

[Continue with all diagnosis descriptions from the prompt]

Available diagnoses list: " . implode( ', ', $diagnosis_list );
```

---

### 9. Update Question Limit Instructions

**File**: `functions/ai-integration.php`  
**Lines**: 4692-4714

**Current**: English instructions with emoji warnings

**Required**: Translate to Arabic and match the format in the new prompt

**New Code**:
```php
$question_limit_instruction = "
## حدود الأسئلة:
- الحد الأقصى: {$max_questions}
- عدد الأسئلة الحالي: {$ai_questions_count}
- عند الوصول للحد الأقصى يجب إنهاء المحادثة وإرجاع status = \"complete\".

";
```

---

### 10. Update Diagnosis Validation Logic

**File**: `functions/ai-integration.php`  
**Lines**: 4839-4864

**Required**: Add validation for new general assessment types:
- `general_assessment_pediatric`
- `general_assessment_doctor`
- `general_assessment_therapist`

**Implementation**: Ensure these diagnosis names are recognized in the validation loop.

---

## Implementation Checklist

- [ ] **1. Replace default prompt** with new Arabic prompt
- [ ] **2. Update language instruction** to support dialect matching
- [ ] **3. Add mandatory initial questions** instructions
- [ ] **4. Add emergency detection** method and integration
- [ ] **5. Update response format** in system prompt (add ai_diagnosis, therapist_summary, patient_summary)
- [ ] **6. Remove confidence field** from all code
- [ ] **7. Add general assessment type logic** (pediatric/doctor/therapist)
- [ ] **8. Add helper functions**: `extract_patient_age()`, `detect_medication_need()`
- [ ] **9. Update response parsing** to handle new fields
- [ ] **10. Update diagnosis storage** to save new fields
- [ ] **11. Update response return** to include new fields
- [ ] **12. Add detailed diagnosis descriptions** to system prompt
- [ ] **13. Update question limit instructions** to Arabic format
- [ ] **14. Add conversation flow instructions** (natural flow, topic reminders)
- [ ] **15. Update diagnosis validation** to recognize new general assessment types

---

## Testing Requirements

After implementation, test the following scenarios:

1. **Dialect Detection**: Test with different Arabic dialects (Egyptian, Saudi, etc.)
2. **Initial Questions**: Verify mandatory questions are asked at start
3. **Emergency Handling**: Test with suicidal thought keywords
4. **Age Detection**: Test pediatric vs. adult general assessments
5. **Medication Detection**: Test doctor vs. therapist general assessments
6. **Response Format**: Verify all new JSON fields are present
7. **Multiple Diagnoses**: Test handling of overlapping diagnoses
8. **Topic Deviation**: Test reminder when user goes off-topic
9. **Language Switching**: Test non-Arabic language handling
10. **Question Limits**: Verify max questions enforcement

---

## Key Files to Modify

1. **`functions/ai-integration.php`**
   - Lines 21-36: Default prompt
   - Lines 4643-4976: Main processing function
   - Lines 4687-4720: System prompt construction
   - Lines 4794-4975: Response parsing and validation
   - Add new helper methods

2. **`functions/admin/ai-admin-enhanced.php`**
   - Update admin interface if needed for new fields
   - Update prompt editor to support the new format

---

## Notes

- The new prompt is Arabic-first but should handle English conversations
- Dialect detection already exists but needs to be integrated into language instructions
- Emergency handling is critical for patient safety
- The `ai_diagnosis` field allows AI to provide scientific diagnosis names separate from system diagnosis codes
- Therapist and patient summaries are new requirements for better record-keeping
- General assessment types help route patients to appropriate care (pediatric/doctor/therapist)

---

## Migration Considerations

1. **Existing Data**: Existing diagnosis results won't have new fields (`ai_diagnosis`, `therapist_summary`, `patient_summary`)
2. **Backward Compatibility**: Consider handling missing fields gracefully
3. **Database**: May need to update user meta structure
4. **Frontend**: Frontend components may need updates to display new fields

---

## Additional Resources

- Current prompt guidelines: `Patient_Diagnosis_Assistant_Guidelines.md`
- Current flow documentation: `README_ChatGPT_Diagnosis_Flow.md`
- Dialect detection function: `functions/ai-integration.php` line 866
