/* ระบบจองสนามกีฬา — JavaScript เล็กๆ (ไม่พึ่งไลบรารีภายนอก) */
(function () {
  'use strict';

  // เมนูข้างบนมือถือ
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('sideBackdrop');
  var menuBtn = document.getElementById('menuBtn');
  function toggleMenu(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    if (backdrop) backdrop.classList.toggle('open', open);
  }
  if (menuBtn) menuBtn.addEventListener('click', function () { toggleMenu(true); });
  if (backdrop) backdrop.addEventListener('click', function () { toggleMenu(false); });

  function refreshFilePicker(input) {
    var picker = input.closest('.file-picker');
    if (!picker) return;
    var label = input.nextElementSibling;
    var clearButton = picker.querySelector('[data-file-clear]');
    var hasFile = input.files.length > 0;
    if (label) {
      label.textContent = hasFile ? input.files[0].name : 'Choose file';
      label.title = hasFile ? input.files[0].name : 'Choose file';
    }
    if (clearButton) clearButton.disabled = !hasFile;
  }

  document.querySelectorAll('[data-file-picker]').forEach(function (input) {
    var picker = input.closest('.file-picker');
    var clearButton = picker && picker.querySelector('[data-file-clear]');
    input.addEventListener('change', function () { refreshFilePicker(input); });
    if (clearButton) clearButton.addEventListener('click', function () {
      input.value = '';
      refreshFilePicker(input);
    });
    if (input.form) input.form.addEventListener('reset', function () {
      window.setTimeout(function () { refreshFilePicker(input); }, 0);
    });
    refreshFilePicker(input);
  });

  // ยืนยันก่อนส่งฟอร์มที่มี data-confirm
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // ปิดกล่องข้อความ (dialog)
  document.addEventListener('click', function (e) {
    var close = e.target.closest('[data-close-dialog]');
    if (close) {
      var d = close.closest('dialog');
      if (d) d.close();
    }
    // คลิกพื้นหลังเพื่อปิด
    if (e.target.tagName === 'DIALOG') e.target.close();
  });

  function setField(form, name, value) {
    var el = form.elements[name];
    if (el) el.value = value == null ? '' : value;
  }

  // ปุ่มแก้ไขการจอง
  var editDlg = document.getElementById('editBooking');
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-edit-booking]');
    if (!btn || !editDlg) return;
    var f = editDlg.querySelector('form');
    setField(f, 'id', btn.dataset.id);
    setField(f, 'date', btn.dataset.date);
    setField(f, 'start', btn.dataset.start);
    setField(f, 'end', btn.dataset.end);
    setField(f, 'participants', btn.dataset.participants);
    setField(f, 'purpose', btn.dataset.purpose);
    setField(f, 'notes', btn.dataset.notes);
    var code = editDlg.querySelector('[data-fill="code"]');
    if (code) code.textContent = btn.dataset.code || '';
    editDlg.showModal();
  });

  // ฟอร์มเพิ่ม/แก้ไขสนาม (ผู้ดูแล)
  var fieldDlg = document.getElementById('fieldDialog');
  if (fieldDlg) {
    var ff = fieldDlg.querySelector('form');
    var title = fieldDlg.querySelector('[data-field-title]');
    var preview = fieldDlg.querySelector('[data-field-preview]');
    function showPreview(src) {
      if (!preview) return;
      preview.innerHTML = '';
      if (src) {
        var img = document.createElement('img');
        img.src = src; img.alt = ''; img.style.cssText = 'width:100%;height:112px;object-fit:cover;border-radius:8px;margin-top:8px';
        img.onerror = function () { img.remove(); };
        preview.appendChild(img);
      }
    }
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-field-edit]');
      var add = e.target.closest('[data-field-add]');
      if (!btn && !add) return;
      ff.reset();
      var d = (btn || add).dataset;
      setField(ff, 'id', btn ? d.id : '');
      setField(ff, 'code', d.code);
      setField(ff, 'name', btn ? d.name : '');
      setField(ff, 'sport_type', btn ? d.sport : 'ฟุตบอล');
      setField(ff, 'location', btn ? d.location : '');
      setField(ff, 'description', btn ? d.description : '');
      setField(ff, 'status', btn ? d.status : 'available');
      setField(ff, 'image_url', btn ? d.image : '');
      var have = (btn ? d.facilities : '').split(',');
      ff.querySelectorAll('input[name="facilities[]"]').forEach(function (c) { c.checked = have.indexOf(c.value) !== -1; });
      if (title) title.textContent = btn ? 'แก้ไขสนาม' : 'เพิ่มสนาม';
      showPreview(btn ? d.imagePreview : '');
      var imagePicker = ff.querySelector('[data-file-picker]');
      if (imagePicker) refreshFilePicker(imagePicker);
      fieldDlg.showModal();
    });
  }

  // ซ่อนข้อความแจ้งเตือนอัตโนมัติ
  var toasts = document.querySelectorAll('.toast');
  if (toasts.length) {
    setTimeout(function () {
      toasts.forEach(function (t) { t.style.opacity = '0'; });
      setTimeout(function () { toasts.forEach(function (t) { t.remove(); }); }, 450);
    }, 4500);
  }

  // ฟอร์มจอง: กันเลือกเวลาสิ้นสุดก่อนเวลาเริ่ม
  var bookingForm = document.querySelector('form[data-booked-slots]');
  var startSel = bookingForm && bookingForm.elements.start;
  var endSel = bookingForm && bookingForm.elements.end;
  var dateInput = bookingForm && bookingForm.elements.date;
  if (bookingForm && startSel && endSel && dateInput) {
    var bookedSlots = JSON.parse(bookingForm.dataset.bookedSlots || '[]');
    function minutes(time) {
      var parts = String(time).slice(0, 5).split(':');
      return Number(parts[0]) * 60 + Number(parts[1]);
    }
    function hasBooking(date, start, end) {
      return bookedSlots.some(function (slot) {
        return slot.booking_date === date && minutes(slot.start_time) < end && minutes(slot.end_time) > start;
      });
    }
    var sync = function () {
      var date = dateInput.value;
      Array.prototype.forEach.call(startSel.options, function (option) {
        if (!option.value) return;
        var start = minutes(option.value);
        var unavailable = !!date && hasBooking(date, start, start + 60);
        option.disabled = unavailable;
        option.textContent = option.value + (unavailable ? ' (ไม่ว่าง)' : '');
      });
      if (startSel.selectedOptions[0] && startSel.selectedOptions[0].disabled) startSel.value = '';
      var start = startSel.value ? minutes(startSel.value) : null;
      Array.prototype.forEach.call(endSel.options, function (option) {
        if (!option.value) return;
        var end = minutes(option.value);
        var overlaps = !!date && start !== null && end > start && hasBooking(date, start, end);
        option.disabled = !!(start !== null && end <= start) || overlaps;
        option.textContent = option.value + (overlaps ? ' (ไม่ว่าง)' : '');
      });
      if (endSel.selectedOptions[0] && endSel.selectedOptions[0].disabled) endSel.value = '';
    };
    dateInput.addEventListener('change', sync);
    startSel.addEventListener('change', sync);
    sync();
  }
})();
