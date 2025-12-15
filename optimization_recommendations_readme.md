# Optimization Recommendations

This document provides concrete, actionable recommendations to optimize the current ChatGPT-based psychological triage and recommendation system. The focus is on **stability, safety, maintainability, and production readiness**.

---

## 1. Architectural Optimization

### 1.1 Separate Responsibilities (Strongly Recommended)

Split the current monolithic prompt into **three logical layers**:

1. **Conversation Layer**
   - Manages dialogue flow
   - Asks questions
   - Enforces focus and language
   - Handles emergency escalation

2. **Assessment Layer**
   - Interprets collected information
   - Determines `ai_diagnosis`
   - Decides whether the conversation is complete

3. **Mapping / Routing Layer**
   - Maps `ai_diagnosis` → `available_diagnoses`
   - Selects therapist category
   - Never interacts directly with the patient

This separation reduces hallucination risk and makes the system auditable.

---

## 2. Prompt Engineering Improvements

### 2.1 Reduce Prompt Size

Current prompt length increases:
- Cognitive load on the model
- Risk of rule conflicts
- Inconsistent output

**Actions:**
- Remove repeated diagnosis descriptions
- Move examples to a separate developer-only prompt
- Keep only enforceable rules in the system prompt

Target reduction: **30–40%**.

---

### 2.2 Enforce Minimum Data Threshold

Do not allow diagnosis or routing unless the following are known:
- Age
- Whether the speaker is the patient or a proxy
- Primary complaint
- Duration of symptoms
- Functional impact

Add an explicit rule:
> لا يتم اختيار أي تشخيص أو ترشيح معالج قبل استيفاء الحد الأدنى من البيانات الأساسية.

---

## 3. Output Contract Hardening

### 3.1 Status-Driven Field Rules

| status       | ai_diagnosis | diagnosis | therapist_summary | patient_summary |
|-------------|--------------|-----------|-------------------|-----------------|
| incomplete  | empty        | empty     | allowed           | allowed         |
| complete    | required     | required  | required          | required        |

**Never populate diagnosis fields when `status = "incomplete"`.**

---

### 3.2 Validation Layer

Before accepting any response:
- Validate JSON schema
- Reject responses violating status rules
- Re-prompt the model automatically on failure

---

## 4. Conversation Quality Controls

### 4.1 Question Discipline

- Ask **one question at a time**
- Avoid compound questions
- Progress from general → specific

Recommended internal rule:
> كل سؤال يجب أن يضيف معلومة تشخيصية جديدة.

---

### 4.2 Noise Handling

Inputs such as:
- Single characters ("x")
- Emojis
- Empty messages

Should:
- Not increment `question_count`
- Not affect diagnosis confidence
- Trigger a polite restart message

---

## 5. Safety and Compliance

### 5.1 Emergency Override (Mandatory)

If the user mentions:
- Suicidal intent
- Self-harm plans
- Harm to others

The system must:
- Stop diagnostic flow
- Display emergency message
- Avoid JSON routing logic

This rule must override **all other instructions**.

---

### 5.2 Diagnostic Language Control

- Use **non-definitive phrasing** in patient-facing text
- Avoid terms implying final diagnosis
- Reinforce that this is an initial assessment only

---

## 6. Operational Recommendations

### 6.1 Logging and Observability

Log separately:
- Raw user input
- Model output
- Validation failures
- Emergency escalations

This is essential for:
- Quality improvement
- Incident review
- Clinical governance

---

### 6.2 Versioning

Version control:
- System prompts
- Diagnosis taxonomy
- Output schema

Never deploy silent prompt changes.

---

## 7. Final Recommendation

With the above optimizations applied, the system will:
- Behave predictably under edge cases
- Reduce legal and clinical risk
- Scale safely to real users
- Be maintainable by multiple teams

**Current maturity:** Intermediate

**Post-optimization maturity:** Production / Enterprise-ready

---

If needed, this README can be expanded to include:
- Prompt version history
- Regulatory alignment notes
- Therapist matching algorithms
- Automated test cases

