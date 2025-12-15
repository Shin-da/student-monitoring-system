(function(){
  'use strict';

  // Mark that this script is handling the form to prevent conflicts
  window.adminCreateUserHandled = true;

  document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('createUserForm');
    if (!form) return;

    var submitBtn = document.getElementById('submitBtn');

    function showAlert(message, type) {
      // type: 'success' | 'danger' | 'warning'
      var alert = document.createElement('div');
      alert.className = 'alert alert-' + (type || 'success');
      alert.setAttribute('role', 'alert');
      alert.textContent = message;

      form.parentNode.insertBefore(alert, form);
      setTimeout(function(){
        if (alert && alert.parentNode) alert.parentNode.removeChild(alert);
      }, 4000);
    }

    function getValue(id) {
      var el = document.getElementById(id);
      return el ? el.value : '';
    }

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      if (submitBtn) {
        submitBtn.disabled = true;
      }

      var role = getValue('role');
      var payload = {
        name: getValue('name').trim(),
        email: getValue('email').trim(),
        password: getValue('password'),
        role: role
      };

      // Optional fields
      var expiry = getValue('expiryDate');
      var notes = getValue('notes');
      if (expiry) payload.expiry_date = expiry;
      if (notes) payload.notes = notes;

      // Role-specific optional fields mapping to API
      // Students
      var studentIdAsLRN = getValue('student_id');
      var gradeLevel = getValue('grade_level');
      var sectionName = getValue('section_name') || getValue('section');
      if (role === 'student') {
        if (studentIdAsLRN) payload.lrn = studentIdAsLRN;
        if (gradeLevel) payload.grade_level = parseInt(gradeLevel, 10) || null;
        if (sectionName) payload.section_name = sectionName;
      }
      // Teachers/advisers
      var isAdviser = getValue('is_adviser');
      var department = getValue('department');
      var employeeId = getValue('employee_id');
      var subjectSpecialization = getValue('subject_specialization') || getValue('specialization');
      var hireDate = getValue('hire_date');
      
      if (role === 'teacher' || role === 'adviser') {
        if (isAdviser !== '') payload.is_adviser = !!(isAdviser === '1' || isAdviser === 'true' || isAdviser === true);
        if (department) payload.department = department;
        if (employeeId) payload.employee_id = employeeId;
        if (subjectSpecialization) payload.subject_specialization = subjectSpecialization;
        if (hireDate) payload.hire_date = hireDate;
      }

      console.log('Submitting user creation request:', { role: role, payload: payload });

      var base = (window.__BASE_PATH__ || '').replace(/\/$/, '');
      var endpoint = base + '/api/create_user.php';
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      })
      .then(function(res){
        console.log('Response status:', res.status, res.statusText);
        return res.json().catch(function(err){
          console.error('Failed to parse JSON response:', err);
          return {success:false, message:'Invalid server response: ' + res.statusText};
        });
      })
      .then(function(json){
        console.log('API response:', json);
        if (json && json.success) {
          showAlert('User created successfully.', 'success');
          form.reset();
          // If role-specific fields section exists, hide it after reset
          var rs = document.getElementById('roleSpecificFields');
          if (rs) rs.style.display = 'none';
          
          // Dispatch custom event if teacher or adviser was created
          // This allows other modules (like Create Class) to refresh their teacher dropdowns
          if (role === 'teacher' || role === 'adviser') {
            var event = new CustomEvent('teacherCreated', {
              detail: {
                userId: json.user_id || null,
                role: role,
                name: payload.name,
                email: payload.email
              },
              bubbles: true,
              cancelable: true
            });
            document.dispatchEvent(event);
          }
        } else {
          var errorMsg = (json && json.message) || 'Failed to create user.';
          if (json && json.error) {
            errorMsg += ' Error: ' + json.error;
          }
          console.error('User creation failed:', json);
          showAlert(errorMsg, 'danger');
        }
      })
      .catch(function(err){
        console.error('Network/request error:', err);
        showAlert('Network error. Please check console for details and try again.', 'danger');
      })
      .finally(function(){
        if (submitBtn) {
          submitBtn.disabled = false;
        }
      });
    });
  });
})();


