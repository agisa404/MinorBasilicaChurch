
(function () {
  'use strict';
  var form = document.querySelector('form[data-carousel-cropper]');
  if (!form || !window.bootstrap) return;
  var OUTPUT_W = 1200, OUTPUT_H = 500, ASPECT = OUTPUT_W / OUTPUT_H;
  var modalEl = document.getElementById('carouselCropModal');
  var canvas = document.getElementById('carouselCropCanvas');
  var zoomEl = document.getElementById('carouselCropZoom');
  var resetBtn = document.getElementById('carouselCropReset');
  var confirmBtn = document.getElementById('carouselCropConfirm');
  if (!modalEl || !canvas || !zoomEl || !resetBtn || !confirmBtn) return;
  var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  var ctx = canvas.getContext('2d', { alpha: false });
  var activeSlot = null, activeInput = null, activeFlag = null, activeStatus = null;
  var activeObjectUrl = null, img = null;
  var baseScale = 1, zoom = 1, offsetX = 0, offsetY = 0;
  var dragging = false, dragStartX = 0, dragStartY = 0, dragStartOffsetX = 0, dragStartOffsetY = 0;
  var confirmed = false;
  function setCanvasSize() {
    var w = 960;
    canvas.width = w;
    canvas.height = Math.round(w / ASPECT);
  }
  function setStatus(text, warn) {
    if (!activeStatus) return;
    activeStatus.textContent = text || '';
    activeStatus.classList.toggle('text-warning', !!warn);
    activeStatus.classList.toggle('text-secondary', !warn);
  }
  function clampOffsets() {
    if (!img) return;
    var drawnW = img.width * baseScale * zoom;
    var drawnH = img.height * baseScale * zoom;
    var minX = canvas.width - drawnW;
    var minY = canvas.height - drawnH;
    if (minX > 0) minX = 0;
    if (minY > 0) minY = 0;
    if (offsetX > 0) offsetX = 0;
    if (offsetY > 0) offsetY = 0;
    if (offsetX < minX) offsetX = minX;
    if (offsetY < minY) offsetY = minY;
  }
  function draw() {
    if (!img) return;
    ctx.fillStyle = '#000';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    var scale = baseScale * zoom;
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(img, offsetX, offsetY, img.width * scale, img.height * scale);
  }
  function centerAndFit() {
    if (!img) return;
    baseScale = Math.max(canvas.width / img.width, canvas.height / img.height);
    offsetX = (canvas.width - img.width * baseScale) / 2;
    offsetY = (canvas.height - img.height * baseScale) / 2;
    clampOffsets();
    draw();
  }
  function cleanupObjectUrl() {
    if (activeObjectUrl) { try { URL.revokeObjectURL(activeObjectUrl); } catch (e) {} }
    activeObjectUrl = null;
  }
  function clearInputAndFlags(slot, inputEl) {
    if (inputEl) inputEl.value = "";
    var flag = form.querySelector("input[data-carousel-cropped-flag=\"" + slot + "\"]");
    if (flag) flag.value = "0";
    var status = form.querySelector("[data-carousel-status=\"" + slot + "\"]");
    if (status) status.textContent = "";
  }
  function setFileInputFromBlob(inputEl, blob, slot) {
    if (!inputEl) return;
    var dt = new DataTransfer();
    dt.items.add(new File([blob], "carousel_slot_" + slot + ".jpg", { type: "image/jpeg" }));
    inputEl.files = dt.files;
  }
  function exportCroppedBlob(cb) {
    if (!img) return cb(null);
    var scale = baseScale * zoom;
    var cropWImg = canvas.width / scale;
    var cropHImg = canvas.height / scale;
    var srcX = (-offsetX) / scale;
    var srcY = (-offsetY) / scale;
    srcX = Math.max(0, Math.min(srcX, img.width - cropWImg));
    srcY = Math.max(0, Math.min(srcY, img.height - cropHImg));
    var out = document.createElement("canvas");
    out.width = OUTPUT_W;
    out.height = OUTPUT_H;
    var octx = out.getContext("2d", { alpha: false });
    octx.fillStyle = "#000";
    octx.fillRect(0, 0, OUTPUT_W, OUTPUT_H);
    octx.imageSmoothingEnabled = true;
    octx.imageSmoothingQuality = "high";
    octx.drawImage(img, srcX, srcY, cropWImg, cropHImg, 0, 0, OUTPUT_W, OUTPUT_H);
    out.toBlob(function (blob) { cb(blob); }, "image/jpeg", 0.9);
  }
  function openCropper(slot, sourceUrl, inputEl) {
    confirmed = false;
    activeSlot = slot;
    activeInput = inputEl || null;
    activeFlag = form.querySelector("input[data-carousel-cropped-flag=\"" + slot + "\"]");
    activeStatus = form.querySelector("[data-carousel-status=\"" + slot + "\"]");
    if (activeFlag) activeFlag.value = "0";
    setStatus("Cropping required...", true);
    setCanvasSize();
    zoom = 1;
    zoomEl.value = "1";

    img = new Image();
    img.onload = function () {
      centerAndFit();
      modal.show();
    };
    img.onerror = function () {
      setStatus("Unable to load image for cropping.", true);
    };
    img.src = sourceUrl;
  }
  Array.prototype.slice.call(form.querySelectorAll('input[type="file"][data-carousel-slot]')).forEach(function (inputEl) {
    inputEl.addEventListener('change', function () {
      var slot = parseInt(inputEl.getAttribute('data-carousel-slot'), 10);
      var file = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
      if (!slot || !file) {
        clearInputAndFlags(slot, inputEl);
        return;
      }
      cleanupObjectUrl();
      activeObjectUrl = URL.createObjectURL(file);
      openCropper(slot, activeObjectUrl, inputEl);
    });
  });
  Array.prototype.slice.call(form.querySelectorAll('button[data-carousel-crop-current]')).forEach(function (btn) {
    btn.addEventListener('click', function () {
      var slot = parseInt(btn.getAttribute('data-carousel-crop-current'), 10);
      var src = btn.getAttribute('data-carousel-current-src') || '';
      if (!slot || !src) return;
      var inputEl = form.querySelector('input[type="file"][data-carousel-slot="' + slot + '"]');
      cleanupObjectUrl();
      openCropper(slot, src, inputEl);
    });
  });
  zoomEl.addEventListener('input', function () {
    zoom = parseFloat(zoomEl.value || '1') || 1;
    clampOffsets();
    draw();
  });

  resetBtn.addEventListener('click', function () {
    zoom = 1;
    zoomEl.value = '1';
    centerAndFit();
  });

  canvas.addEventListener('pointerdown', function (e) {
    if (!img) return;
    dragging = true;
    canvas.setPointerCapture(e.pointerId);
    dragStartX = e.clientX;
    dragStartY = e.clientY;
    dragStartOffsetX = offsetX;
    dragStartOffsetY = offsetY;
  });

  canvas.addEventListener('pointermove', function (e) {
    if (!dragging) return;
    offsetX = dragStartOffsetX + (e.clientX - dragStartX);
    offsetY = dragStartOffsetY + (e.clientY - dragStartY);
    clampOffsets();
    draw();
  });

  function stopDrag(e) {
    dragging = false;
    try { canvas.releasePointerCapture(e.pointerId); } catch (err) {}
  }

  canvas.addEventListener('pointerup', stopDrag);
  canvas.addEventListener('pointercancel', stopDrag);
  confirmBtn.addEventListener('click', function () {
    if (!activeSlot || !activeFlag) return;
    confirmBtn.disabled = true;
    exportCroppedBlob(function (blob) {
      confirmBtn.disabled = false;
      if (!blob) {
        setStatus('Crop failed. Please try again.', true);
        return;
      }
      confirmed = true;
      setFileInputFromBlob(activeInput, blob, activeSlot);
      activeFlag.value = '1';
      setStatus('Cropped (' + OUTPUT_W + 'x' + OUTPUT_H + ') ready to save.', false);
      modal.hide();
    });
  });
  modalEl.addEventListener('hidden.bs.modal', function () {
    var slot = activeSlot;
    var inputEl = activeInput;
    if (!confirmed && slot && inputEl) {
      clearInputAndFlags(slot, inputEl);
    }
    cleanupObjectUrl();
    img = null;
    activeSlot = null;
    activeInput = null;
    activeFlag = null;
    activeStatus = null;
    confirmed = false;
  });
  form.addEventListener('submit', function (e) {
    var badSlot = null;
    Array.prototype.slice.call(form.querySelectorAll('input[type="file"][data-carousel-slot]')).some(function (inputEl) {
      var slot = parseInt(inputEl.getAttribute('data-carousel-slot'), 10);
      if (!slot) return false;
      if (inputEl.files && inputEl.files.length) {
        var flag = form.querySelector('input[data-carousel-cropped-flag="' + slot + '"]');
        if (!flag || flag.value !== '1') {
          badSlot = slot;
          return true;
        }
      }
      return false;
    });

    if (badSlot) {
      e.preventDefault();
      var status = form.querySelector('[data-carousel-status="' + badSlot + '"]');
      if (status) {
        status.textContent = 'Please crop this photo before saving.';
        status.classList.add('text-warning');
      }
      alert('Please crop carousel slot ' + badSlot + ' before saving.');
    }
  });
})();
