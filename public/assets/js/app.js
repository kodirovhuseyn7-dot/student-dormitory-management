// Lab 11 — SDMS JavaScript (ES2020+)
const debounce = (fn, delay = 300) => {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
};

// 1. ВАЛИДАТСИЯИ ШАКЛИ АРИЗА
const messages = {
  full_name: ["Ному насабро ворид кунед.", "Ному насаб аз 80 аломат зиёд набошад."],
  student_code: ["Рақами донишҷӯиро ворид кунед.", "Формати рақами донишҷӯӣ нодуруст аст."],
  email: ["Почтаи электрониро ворид кунед.", "Формати почтаи электронӣ нодуруст аст."],
  phone: ["Рақами телефонро ворид кунед.", "Формати рақами телефон нодуруст аст."],
  room_number: ["Ҳуҷраро интихоб кунед."],
  move_in: ["Санаи воридшавиро интихоб кунед."],
  move_out: ["Санаи баромаданро интихоб кунед."]
};

function validateField(field) {
  const v = field.validity;
  let message = "";
  const custom = field.validationMessage;
  if (v.customError) message = custom;
  else if (v.valueMissing) message = messages[field.name]?.[0] || "Ин майдонро пур кунед.";
  else if (v.typeMismatch) message = messages[field.name]?.[1] || "Формат нодуруст аст.";
  else if (v.patternMismatch) message = messages[field.name]?.[1] || "Формат нодуруст аст.";
  else if (v.tooLong) message = messages[field.name]?.[1] || "Матн хеле дароз аст.";

  field.classList.toggle("is-invalid", Boolean(message));
  field.setAttribute("aria-invalid", message ? "true" : "false");
  const feedback = document.getElementById(`${field.id}-error`);
  if (feedback) feedback.textContent = message;
  return !message;
}

const applicationForm = document.querySelector("#dorm-application-form");
if (applicationForm) {
  const start = applicationForm.elements.move_in;
  const end = applicationForm.elements.move_out;

  const validateDates = () => {
    end.setCustomValidity("");
    end.min = start.value;
    if (start.value && end.value && end.value < start.value) {
      end.setCustomValidity("Санаи баромадан набояд пеш аз санаи воридшавӣ бошад.");
    }
    validateField(end);
  };

  start.addEventListener("change", validateDates);
  applicationForm.addEventListener("focusout", e => {
    if (e.target.matches("input, select")) {
      if (e.target === start || e.target === end) validateDates();
      else validateField(e.target);
    }
  });
  applicationForm.addEventListener("input", e => {
    if (e.target.matches("input, select") && e.target.classList.contains("is-invalid")) {
      validateField(e.target);
    }
  });
  applicationForm.addEventListener("submit", e => {
    e.preventDefault();
    validateDates();
    let firstInvalid = null;
    applicationForm.querySelectorAll("input, select").forEach(field => {
      if (!validateField(field) && !firstInvalid) firstInvalid = field;
    });
    const result = document.querySelector("#application-form-result");
    if (firstInvalid) {
      firstInvalid.focus();
      result.textContent = "Лутфан хатогиҳоро ислоҳ кунед.";
    } else {
      result.textContent = "Валидатсия бомуваффақият гузашт. Шакл барои фиристодан омода аст.";
    }
  });
}

// 2. ҶУСТУҶӮИ ФАВРӢ: fetch + debounce + AbortController
const searchInput = document.querySelector("#student-search");
const studentsBody = document.querySelector("#students-tbody");
if (searchInput && studentsBody) {
  const status = document.querySelector("#student-search-status");
  let controller;

  function renderStudents(students) {
    if (!students.length) {
      const row = document.createElement("tr");
      const cell = document.createElement("td");
      cell.colSpan = 7;
      cell.className = "text-center text-secondary py-3";
      cell.textContent = "Донишҷӯён ёфт нашуданд.";
      row.append(cell);
      studentsBody.replaceChildren(row);
      return;
    }
    const rows = students.map(student => {
      const tr = document.createElement("tr");
      const values = [
        student.id, student.full_name, student.student_code,
        student.course, student.faculty, student.room || "—"
      ];
      values.forEach(value => {
        const td = document.createElement("td");
        td.textContent = String(value); // XSS-safe: ҳеҷ гоҳ innerHTML истифода намешавад
        tr.append(td);
      });
      const td = document.createElement("td");
      const badge = document.createElement("span");
      badge.className = "room-status " +
        (student.status === "active" ? "status-available" : "status-pending");
      badge.textContent = ({active:"Фаъол", inactive:"Ғайрифаъол", no_room:"Бе ҳуҷра"})[student.status] || "Номаълум";
      td.append(badge);
      tr.append(td);
      return tr;
    });
    studentsBody.replaceChildren(...rows);
  }

  async function search(query) {
    controller?.abort();
    controller = new AbortController();
    status.textContent = "Ҷустуҷӯ…";
    studentsBody.setAttribute("aria-busy", "true");
    try {
      const response = await fetch(`../api/students/index.php?q=${encodeURIComponent(query)}`, {
        headers: {Accept:"application/json"},
        signal: controller.signal
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      renderStudents(data);
      status.textContent = `Натиҷаҳо: ${data.length}`;
    } catch (error) {
      if (error.name !== "AbortError") status.textContent = "Хатои пайвастшавӣ ба API.";
    } finally {
      studentsBody.removeAttribute("aria-busy");
    }
  }
  const delayedSearch = debounce(search, 300);
  searchInput.addEventListener("input", () => delayedSearch(searchInput.value.trim()));
  search("");
}

// 3. РАВЗАНАИ ТАСДИҚИ АМАЛИ ХАТАРНОК
const cancelModal = document.querySelector("#confirm-cancel-modal");
if (cancelModal && window.bootstrap) {
  const modal = bootstrap.Modal.getOrCreateInstance(cancelModal);
  const form = document.querySelector("#confirm-cancel-form");
  const name = document.querySelector("#confirm-cancel-name");
  let trigger = null;

  document.addEventListener("click", event => {
    const button = event.target.closest("[data-cancel-url]");
    if (!button) return;
    trigger = button;
    form.action = button.dataset.cancelUrl;
    name.textContent = button.dataset.applicationName || "ин ариза";
    modal.show();
  });
  cancelModal.addEventListener("hidden.bs.modal", () => trigger?.focus());
}
