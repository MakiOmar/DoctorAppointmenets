# 🧠 AI Psychological Assessment System – Architecture Fix Guide

## Objective

Fix the current AI-driven psychological assessment flow to be **stable, logically correct, and production-ready**, while preserving:

- Human, gradual interview experience  
- Strict safety rules  
- Server-side control over question limits  
- Final structured JSON diagnosis output  

---

## 🚨 Core Problem (Must Be Understood First)

The system currently has a **fundamental architectural conflict**:

- The backend expects a **multi-turn conversational interview**
- BUT the OpenAI request **forces `response_format: json_object` on every call**

This is **logically impossible**.

> A model cannot:
> - Ask questions  
> - Wait for user replies  
> - Manage conversation state  
> **and**
> - Be forced to output strict JSON at every turn  

This causes:
- Empty responses  
- Whitespace flooding  
- Invalid JSON  
- Unstable behavior  

---

## ✅ Required Architectural Fix (Non-Negotiable)

### 🔑 Golden Rule

**Separate the system into TWO DISTINCT PHASES:**

1. **Interview Phase (Conversational)**
2. **Final Diagnosis Phase (JSON Only)**

---

## 🧩 Phase 1: Interview Phase (Conversation Mode)

### Purpose
- Ask one question at a time  
- Collect required data  
- Maintain a natural conversation  
- NO diagnosis  

### Backend Rules
- ❌ DO NOT use `response_format`  
- ❌ DO NOT expect JSON  
- ✅ Expect plain Arabic text  
- ✅ Count questions **server-side only**  

### OpenAI Request (Interview Phase)

```php
$data = [
    'model'       => $model,
    'messages'    => $messages,
    'max_tokens'  => $max_tokens,
    'temperature' => $temperature,
];
```

> IMPORTANT: Do **not** include `response_format`.

### Prompt Rules (Interview Prompt)
- Ask **exactly one question**
- End every response with a question mark
- No diagnosis
- No JSON
- Human, supportive tone
- Arabic only

---

## 🧩 Phase 2: Final Diagnosis Phase (JSON Mode)

### When to Trigger
The backend decides this, NOT the model.

Trigger when:
- Minimum required data is collected
- OR maximum question limit is reached
- OR safety condition forces termination

### Backend Rules
- ✅ Use `response_format: json_object`
- ❌ No questions allowed
- ❌ No conversational text
- ✅ Single final response

### OpenAI Request (Final Phase)

```php
$data = [
    'model'       => $model,
    'messages'    => $final_messages,
    'max_tokens'  => $max_tokens,
    'temperature' => 0.2,
    'response_format' => [ 'type' => 'json_object' ],
];
```

---

## 📦 Final JSON Contract (Strict)

The model must return **only this JSON**:

```json
{
  "ai_diagnosis": "",
  "diagnosis": "",
  "reasoning": "",
  "status": "complete",
  "question_count": 0,
  "therapist_summary": "",
  "patient_summary": ""
}
```

### Rules
- `status` MUST be `"complete"`
- All fields MUST be filled
- No extra keys
- No text outside JSON

---

## ❌ What MUST Be Removed or Avoided

Cursor AI **must not** do any of the following:

- ❌ Force `response_format` during interview
- ❌ Ask questions inside JSON
- ❌ Extract questions from `reasoning`
- ❌ Let the model count questions
- ❌ Rely on `{question_count}` placeholders inside prompts
- ❌ Combine interview + diagnosis in one response

---

## ✅ Server Is the Source of Truth

The backend controls:

- Question counting
- Min / max enforcement
- Stage transitions
- Safety overrides
- Session state

The AI model:
- Asks questions (Phase 1)
- Produces final JSON (Phase 2)
- NOTHING ELSE

---

## 🧠 Mental Model for Cursor AI

Think of the AI as:

🎤 **An interviewer**  
➜ then  
📝 **A report generator**  

Never both at the same time.

---

## ✅ Acceptance Criteria

- No empty or padded responses
- No JSON during conversation
- Final diagnosis always valid JSON
- No duplicated questions
- Stable behavior across sessions
- No hallucinated structure

---

## ⚠️ Final Note

This is **not a prompt problem**.  
This is **a state-management and contract problem**.

Once the two-phase architecture is implemented, **any reasonable prompt will work correctly**.
