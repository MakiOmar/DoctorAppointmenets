# Patient Diagnosis Assistant — Forced JSON Setup Guidelines

## Overview
This document provides guidelines for integrating the OpenAI Chat Completions API with a **forced JSON output format** using `response_format={ "type": "json_object" }`.  
This ensures all AI responses are **machine-readable JSON** that can be directly parsed by your backend.

---

## Objective
- Restrict the AI assistant to suggest **only from a predefined list** of diagnoses.
- Always return output in a **fixed JSON structure**.
- Ensure **predictability** and **data integrity** for integration with the medical assistant system.

---

## Key Steps

### 1. Prepare the Diagnoses List
- Create a **final, approved list** of diagnoses your system supports.
- Store the list in your backend (e.g., database, configuration file).
- Include for each diagnosis:
  - Name
  - Common symptoms
  - Short description

---

### 2. Define the Output Structure
- The assistant must always return the same JSON keys:
  - `diagnosis` — the selected diagnosis name from the predefined list
  - `confidence` — one of `low`, `medium`, or `high`
  - `reasoning` — short explanation of why this diagnosis was chosen

---

### 3. Write Clear System Instructions
- Tell the model:
  - Only choose from the predefined list.
  - Never invent or suggest conditions outside the list.
  - Always return **only** the agreed JSON structure.
- This step is crucial for **data reliability**.

---

### 4. Use `response_format` for Guaranteed JSON
- Set `response_format` to `{"type": "json_object"}` in your API request.
- This enforces that the response will always be valid JSON.
- This avoids extra text, Markdown, or formatting issues.

---

### 5. Backend Parsing and Validation
- Parse the JSON directly in your backend.
- Check that the `diagnosis` is in your predefined list before showing it.
- Handle cases where no diagnosis matches the symptoms.

---

### 6. Disclaimers & Safety
- Always show a medical disclaimer in the UI:
  > "This is not medical advice. Please consult a qualified healthcare provider."
- Keep all patient inputs and AI outputs **stored securely** for audit purposes.
- Never display unreviewed AI results as final medical diagnoses.

---

### 7. Testing
- Test with a variety of symptom inputs.
- Ensure the AI **never** returns diagnoses outside the list.
- Confirm JSON output parsing works in all cases.

---

## Best Practices
- Keep temperature low (0–0.2) for more consistent outputs.
- Periodically review the AI’s performance and update instructions if necessary.
- Limit and secure your API key using environment variables.
- Ensure your system is GDPR/HIPAA-compliant if applicable.

---

## Outcome
By following these guidelines:
- Your assistant will be **consistent** in output.
- JSON data will be **machine-parseable** every time.
- Diagnoses will be **limited** to your approved medical list.
- Patients will receive clear, structured, and safe AI suggestions.
