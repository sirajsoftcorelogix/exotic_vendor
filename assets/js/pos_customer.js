(function (window) {
  'use strict';

  function posCustomerToast(msg, color) {
    if (typeof window.showPosMessageModal === 'function') {
      var tone = color === 'green' ? 'success' : color === 'violet' || color === 'blue' ? 'info' : color === 'red' ? 'error' : 'warning';
      window.showPosMessageModal({
        title: tone === 'success' ? 'Success' : tone === 'error' ? 'Error' : 'Notice',
        message: String(msg || ''),
        tone: tone
      });
      return;
    }
    if (typeof window.showToast === 'function') {
      window.showToast(msg, color || 'red');
      return;
    }
    if (typeof window.toast === 'function') {
      window.toast(msg, color || 'red');
      return;
    }
    if (typeof console !== 'undefined' && console.warn) {
      console.warn('[POS customer]', msg);
    }
  }

  window.getSelectedCustomerId = function () {
    var sel = document.getElementById('customerSelect');
    var fromSelect = '';
    if (sel) {
      if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.select2) {
        fromSelect = window.jQuery(sel).val();
      } else {
        fromSelect = sel.value;
      }
    }
    if (Array.isArray(fromSelect)) {
      fromSelect = fromSelect[0] || '';
    }
    return (
      (fromSelect && String(fromSelect)) ||
      (window.POS_SESSION_CUSTOMER_ID && String(window.POS_SESSION_CUSTOMER_ID)) ||
      ''
    );
  };

  window.focusPosCustomerSelect = function () {
    var sel = document.getElementById('customerSelect');
    if (!sel) {
      return;
    }
    if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.select2) {
      var $sel = window.jQuery(sel);
      if ($sel.data('select2')) {
        $sel.select2('open');
        return;
      }
    }
    sel.focus();
  };

  window.updatePosCustomerLabels = function (name, phone, email) {
    var nameText =
      name != null && String(name).trim() !== '' ? String(name).trim() : 'Walk-in Customer';
    var phoneText = phone != null ? String(phone).trim() : '';
    var emailText = email != null ? String(email).trim() : '';
    var subText = phoneText !== '' ? phoneText : (emailText !== '' ? emailText : '-');
    ['selectedCustomerNameCart', 'posCartTableCustomerName'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) {
        el.textContent = nameText;
      }
    });
    ['selectedCustomerPhoneCart', 'posCartTableCustomerPhone'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) {
        el.textContent = subText;
      }
    });
  };

  window.validatePosCustomerSelected = function () {
    var customerId = window.getSelectedCustomerId();
    if (!customerId) {
      if (typeof window.showPosMessageModal === 'function') {
        window.showPosMessageModal({
          title: 'Customer required',
          message: 'Please select customer first',
          tone: 'warning',
          onClose: function () {
            window.focusPosCustomerSelect();
          }
        });
      } else {
        posCustomerToast('Please select customer first', 'red');
        window.focusPosCustomerSelect();
      }
      return '';
    }
    return customerId;
  };

  function fetchPosCountryStates(countryCode) {
    var country = String(countryCode || 'IN').trim().toUpperCase().substring(0, 2) || 'IN';
    var stateMap = window.POS_COUNTRY_STATES || {};
    if (Array.isArray(stateMap[country]) && stateMap[country].length) {
      return Promise.resolve(stateMap[country]);
    }
    return fetch(
      'index.php?page=pos_register&action=states-by-country&country=' + encodeURIComponent(country),
      {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
      }
    )
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        window.POS_COUNTRY_STATES = window.POS_COUNTRY_STATES || {};
        window.POS_COUNTRY_STATES[country] = Array.isArray(data) ? data : [];
        if (country === 'IN') {
          window.POS_INDIA_STATES = window.POS_COUNTRY_STATES[country];
        }
        return window.POS_COUNTRY_STATES[country];
      })
      .catch(function () {
        window.POS_COUNTRY_STATES = window.POS_COUNTRY_STATES || {};
        window.POS_COUNTRY_STATES[country] = [];
        return [];
      });
  }

  function populatePosStateSelect(selectEl, states, selectedValue) {
    if (!selectEl) {
      return;
    }
    var selected = String(selectedValue || '').trim();
    var selectedLower = selected.toLowerCase();
    var html = '<option value="">Select state</option>';
    (states || []).forEach(function (state) {
      var name = String((state && state.name) || '').trim();
      if (!name) {
        return;
      }
      var esc = name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
      html += '<option value="' + esc + '">' + esc + '</option>';
    });
    selectEl.innerHTML = html;
    if (selected) {
      var matched = false;
      Array.prototype.forEach.call(selectEl.options, function (opt) {
        if (opt.value.toLowerCase() === selectedLower) {
          opt.selected = true;
          matched = true;
        }
      });
      if (!matched) {
        var opt = document.createElement('option');
        opt.value = selected;
        opt.textContent = selected;
        opt.selected = true;
        selectEl.appendChild(opt);
      }
    }
  }

  function resetPosStateSelect(selectEl, message) {
    if (!selectEl) {
      return;
    }
    var label = message || 'Select state';
    var esc = label.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    selectEl.innerHTML = '<option value="">' + esc + '</option>';
    selectEl.value = '';
  }

  window.syncCustomerStateSelect = function (countryId, stateId, preferredValue) {
    var countryEl = document.getElementById(countryId);
    var stateEl = document.getElementById(stateId);
    var textEl = document.getElementById(stateId + '_text');
    if (!countryEl || !stateEl || !textEl) {
      return Promise.resolve();
    }

    var fieldName =
      stateEl.getAttribute('data-field-name') ||
      textEl.getAttribute('data-field-name') ||
      stateEl.name ||
      textEl.name;
    if (fieldName) {
      stateEl.setAttribute('data-field-name', fieldName);
      textEl.setAttribute('data-field-name', fieldName);
    }
    var country = String(countryEl.value || 'IN').trim().toUpperCase().substring(0, 2) || 'IN';
    var selected =
      preferredValue !== undefined
        ? preferredValue
        : stateEl.classList.contains('hidden')
          ? textEl.value
          : stateEl.value;
    if (country !== 'IN' && country !== 'US') {
      textEl.value = selected || stateEl.value || textEl.value;
      textEl.name = fieldName;
      stateEl.name = '';
      stateEl.classList.add('hidden');
      textEl.classList.remove('hidden');
      return Promise.resolve();
    }

    resetPosStateSelect(stateEl, 'Loading states...');
    textEl.classList.add('hidden');
    stateEl.classList.remove('hidden');
    return fetchPosCountryStates(country).then(function (states) {
      populatePosStateSelect(stateEl, states, selected);
      stateEl.name = fieldName;
      textEl.name = '';
      textEl.classList.add('hidden');
      stateEl.classList.remove('hidden');
    });
  };

  function syncCustomerCountryStateFields() {
    return Promise.all([
      window.syncCustomerStateSelect('customer_country', 'customer_state'),
      window.syncCustomerStateSelect('customer_shipping_country', 'customer_shipping_state')
    ]);
  }

  function fetchCustomerDetailsForModal(customerId) {
    if (!customerId) return;
    fetch('index.php?page=pos_register&action=customer-order-info&customer_id=' + encodeURIComponent(customerId), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.success) return;

        var b = data.billing || {};
        var s = data.shipping || {};

        var setVal = function (name, val) {
          var input = document.querySelector('#customerForm [name="' + name + '"]');
          if (input) {
            input.value = val != null ? String(val) : '';
          }
        };

        setVal('first_name', b.first_name || '');
        setVal('last_name', b.last_name || '');
        setVal('mobile', b.phone || '');
        setVal('cus_email', b.email || '');
        setVal('address_line1', b.address1 || '');
        setVal('address_line2', b.address2 || '');
        setVal('city', b.city || '');
        setVal('zipcode', b.zip || '');
        setVal('gstin', b.gstin || '');

        var countrySel = document.getElementById('customer_country');
        if (countrySel) {
          countrySel.value = b.country || 'IN';
        }

        window.syncCustomerStateSelect('customer_country', 'customer_state', b.state || '');

        setVal('shipping_first_name', s.shipping_first_name || b.first_name || '');
        setVal('shipping_last_name', s.shipping_last_name || b.last_name || '');
        setVal('shipping_mobile', s.sphone || b.phone || '');
        setVal('shipping_email', s.shipping_email || s.email || b.email || '');
        setVal('shipping_address_line1', s.saddress1 || '');
        setVal('shipping_address_line2', s.saddress2 || '');
        setVal('shipping_city', s.scity || '');
        setVal('shipping_zipcode', s.szip || '');

        var sCountrySel = document.getElementById('customer_shipping_country');
        if (sCountrySel) {
          sCountrySel.value = s.scountry || b.country || 'IN';
        }

        window.syncCustomerStateSelect('customer_shipping_country', 'customer_shipping_state', s.sstate || '');
      })
      .catch(function (err) {
        if (typeof console !== 'undefined' && console.error) {
          console.error('Error fetching customer details for edit modal:', err);
        }
      });
  }

  window.openCustomerModal = function (editCustomerId) {
    var modal = document.getElementById('customerModal');
    if (!modal) {
      return;
    }

    var form = document.getElementById('customerForm');
    if (form) {
      form.reset();
    }

    var sameAddrCb = document.getElementById('sameAddress');
    if (sameAddrCb) {
      sameAddrCb.checked = false;
    }

    var titleEl = document.getElementById('customerModalTitle');
    var submitBtn = document.getElementById('customerModalSubmitBtn');
    var idInput = document.getElementById('customer_modal_id');

    var cid = editCustomerId || (editCustomerId === undefined ? '' : editCustomerId);
    if (idInput) {
      idInput.value = cid ? String(cid) : '';
    }

    if (cid) {
      if (titleEl) titleEl.textContent = 'Edit Customer';
      if (submitBtn) submitBtn.textContent = 'Update Customer';
      fetchCustomerDetailsForModal(cid);
    } else {
      if (titleEl) titleEl.textContent = 'Add Customer';
      if (submitBtn) submitBtn.textContent = 'Save Customer';
    }

    modal.classList.remove('hidden');
    syncCustomerCountryStateFields();
  };

  window.editSelectedCustomer = function () {
    var customerId = window.getSelectedCustomerId();
    if (customerId) {
      window.openCustomerModal(customerId);
    } else {
      window.openCustomerModal();
    }
  };

  window.closeCustomerModal = function () {
    var modal = document.getElementById('customerModal');
    if (modal) {
      modal.classList.add('hidden');
    }
  };

  window.copyBilling = function () {
    var checkbox = document.getElementById('sameAddress');
    if (!checkbox) {
      return;
    }
    var map = {
      first_name: 'shipping_first_name',
      last_name: 'shipping_last_name',
      mobile: 'shipping_mobile',
      cus_email: 'shipping_email',
      address_line1: 'shipping_address_line1',
      address_line2: 'shipping_address_line2',
      city: 'shipping_city',
      country: 'shipping_country',
      state: 'shipping_state',
      zipcode: 'shipping_zipcode'
    };

    Object.keys(map).forEach(function (billingField) {
      var shippingField = map[billingField];
      var billingInput = document.querySelector('[name="' + billingField + '"]');
      var shippingInput = document.querySelector('[name="' + shippingField + '"]');
      if (!billingInput || !shippingInput) {
        return;
      }

      if (checkbox.checked) {
        var syncShippingValue = function () {
          shippingInput.value = billingInput.value;
          if (billingField === 'country') {
            var billingState = document.querySelector('[name="state"]');
            window.syncCustomerStateSelect(
              'customer_shipping_country',
              'customer_shipping_state',
              billingState ? billingState.value : ''
            );
          }
          if (billingField === 'state') {
            window.syncCustomerStateSelect(
              'customer_shipping_country',
              'customer_shipping_state',
              billingInput.value
            );
          }
        };
        syncShippingValue();
        shippingInput.classList.add('bg-gray-100');
        billingInput.addEventListener('input', function () {
          if (checkbox.checked) {
            syncShippingValue();
          }
        });
        billingInput.addEventListener('change', function () {
          if (checkbox.checked) {
            syncShippingValue();
          }
        });
      } else {
        shippingInput.classList.remove('bg-gray-100');
      }
    });
  };

  function formatCustomer(data) {
    if (!data.id) {
      return data.text;
    }
    var name = data.name || '';
    var phone = data.phone || '';
    var email = data.email || '';
    if ((!name || (!phone && !email)) && data.element && typeof window.jQuery !== 'undefined') {
      var el = window.jQuery(data.element);
      name = name || String(el.data('name') || '');
      phone = phone || String(el.data('phone') || '');
      email = email || String(el.data('email') || '');
    }
    if (!name) {
      name = String(data.text || '')
        .split('|')[0]
        .trim();
    }
    var subText = phone ? (phone + (email ? ' | ' + email : '')) : (email || '');
    return window.jQuery(
      '<div><div style="font-weight:600">' +
        name +
        '</div><div style="font-size:11px;color:#777">' +
        subText +
        '</div></div>'
    );
  }

  function formatCustomerSelection(data) {
    if (!data.id) {
      return data.text;
    }
    var name = data.name || '';
    if (!name && data.element && typeof window.jQuery !== 'undefined') {
      name = window.jQuery(data.element).data('name') || '';
    }
    return name || data.text;
  }

  function postSetCustomer(customerId) {
    return fetch('index.php?page=pos_register&action=set-customer', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: 'customer_id=' + encodeURIComponent(customerId || '')
    });
  }

  function initPosCustomerSelect() {
    var sel = document.getElementById('customerSelect');
    if (!sel || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.select2) {
      return;
    }
    var $cust = window.jQuery(sel);
    if ($cust.data('select2')) {
      return;
    }
    $cust.select2({
      placeholder: 'Type at least 2 characters to search…',
      allowClear: true,
      width: '100%',
      minimumInputLength: 2,
      ajax: {
        url: 'index.php?page=pos_register&action=customer-search',
        type: 'GET',
        dataType: 'json',
        delay: 320,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        data: function (params) {
          return { q: params.term || '' };
        },
        processResults: function (data) {
          if (!data || !data.success || !Array.isArray(data.customers)) {
            return { results: [] };
          }
          return {
            results: data.customers.map(function (c) {
              var sub = (c.phone || '').trim() || (c.email || '').trim();
              var disp = c.display || ((c.name || '') + (sub ? ' | ' + sub : ''));
              return {
                id: String(c.id),
                text: disp,
                name: c.name || '',
                phone: c.phone || '',
                email: c.email || ''
              };
            })
          };
        },
        cache: true
      },
      templateResult: formatCustomer,
      templateSelection: formatCustomerSelection
    });

    $cust.on('select2:select', function (e) {
      var d = e.params.data;
      window.POS_SESSION_CUSTOMER_ID = d.id ? String(d.id) : '';
      postSetCustomer(d.id || '');
      window.updatePosCustomerLabels(d.name, d.phone, d.email);
    });

    $cust.on('select2:clear', function () {
      window.POS_SESSION_CUSTOMER_ID = '';
      postSetCustomer('');
      window.updatePosCustomerLabels('', '', '');
    });

    if (window.POS_INITIAL_CUSTOMER && window.POS_INITIAL_CUSTOMER.id) {
      var ic = window.POS_INITIAL_CUSTOMER;
      var opt = new Option(ic.text || ic.name || '', String(ic.id), true, true);
      opt.setAttribute('data-name', ic.name || '');
      opt.setAttribute('data-phone', ic.phone || '');
      opt.setAttribute('data-email', ic.email || '');
      $cust.append(opt);
      $cust.val(String(ic.id)).trigger('change');
      window.updatePosCustomerLabels(ic.name, ic.phone, ic.email);
    }
  }

  function initPosCustomerModalForm() {
    var customerForm = document.getElementById('customerForm');
    if (!customerForm) {
      return;
    }

    syncCustomerCountryStateFields();
    [
      ['customer_country', 'customer_state'],
      ['customer_shipping_country', 'customer_shipping_state']
    ].forEach(function (pair) {
      var countryEl = document.getElementById(pair[0]);
      if (countryEl) {
        countryEl.addEventListener('change', function () {
          window.syncCustomerStateSelect(pair[0], pair[1], '');
        });
      }
    });

    customerForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!customerForm.checkValidity()) {
        customerForm.reportValidity();
        return;
      }

      var formData = new FormData(customerForm);
      fetch('index.php?page=pos_register&action=add-customer', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      })
        .then(function (res) {
          return res.text().then(function (text) {
            try {
              return JSON.parse(text.replace(/^\uFEFF/, '').trim());
            } catch (err) {
              throw new Error('Server did not return JSON.');
            }
          });
        })
        .then(function (data) {
          if (!data.success) {
            posCustomerToast(data.message || 'Could not save customer', 'red');
            return;
          }

          var select = document.getElementById('customerSelect');
          if (!select) {
            return;
          }

          var idStr = String(data.customer.id);
          var phoneOrEmail = (data.customer.phone || '').trim() || (data.customer.email || '').trim();
          var label = (data.customer.name || '') + (phoneOrEmail ? ' (' + phoneOrEmail + ')' : '');
          window.POS_SESSION_CUSTOMER_ID = idStr;

          if (window.jQuery && window.jQuery.fn.select2) {
            var $s = window.jQuery(select);
            var existingOpt = $s.find('option[value="' + idStr + '"]');
            if (existingOpt.length) {
              existingOpt.text(label);
              existingOpt.attr('data-name', data.customer.name || '');
              existingOpt.attr('data-phone', data.customer.phone || '');
              existingOpt.attr('data-email', data.customer.email || '');
            } else {
              var opt = new Option(label, idStr, true, true);
              opt.setAttribute('data-name', data.customer.name || '');
              opt.setAttribute('data-phone', data.customer.phone || '');
              opt.setAttribute('data-email', data.customer.email || '');
              $s.append(opt);
            }
            $s.val(idStr).trigger('change');
          } else {
            var existingOption = select.querySelector('option[value="' + idStr + '"]');
            if (existingOption) {
              existingOption.textContent = label;
              existingOption.setAttribute('data-name', data.customer.name || '');
              existingOption.setAttribute('data-phone', data.customer.phone || '');
              existingOption.setAttribute('data-email', data.customer.email || '');
            } else {
              var option = document.createElement('option');
              option.value = idStr;
              option.textContent = label;
              option.setAttribute('data-name', data.customer.name || '');
              option.setAttribute('data-phone', data.customer.phone || '');
              option.setAttribute('data-email', data.customer.email || '');
              select.appendChild(option);
            }
            select.value = idStr;
          }

          postSetCustomer(idStr);
          window.updatePosCustomerLabels(data.customer.name, data.customer.phone, data.customer.email);
          posCustomerToast(data.message || (data.is_update ? 'Customer updated' : 'Customer saved'), 'green');
          window.closeCustomerModal();
        })
        .catch(function (err) {
          posCustomerToast(err.message || 'Save customer failed', 'red');
        });
    });
  }

  function bootPosCustomerUi() {
    initPosCustomerSelect();
    initPosCustomerModalForm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPosCustomerUi);
  } else {
    bootPosCustomerUi();
  }
})(window);
