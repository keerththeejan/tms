<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="employeeForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeModalLabel">New Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" id="empFormCsrf">
          <input type="hidden" name="emp_action" value="save">
          <input type="hidden" name="id" id="empFormId" value="0">

          <div class="accordion" id="empAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#empSecPersonal">Personal Information</button></h2>
              <div id="empSecPersonal" class="accordion-collapse collapse show" data-bs-parent="#empAccordion">
                <div class="accordion-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Employee ID</label>
                      <div class="btn-group w-100 mb-1" role="group">
                        <input type="radio" class="btn-check" name="code_mode" id="empCodeAuto" value="auto" checked>
                        <label class="btn btn-outline-secondary btn-sm" for="empCodeAuto">Auto</label>
                        <input type="radio" class="btn-check" name="code_mode" id="empCodeManual" value="manual">
                        <label class="btn btn-outline-secondary btn-sm" for="empCodeManual">Manual</label>
                      </div>
                      <input type="text" class="form-control" name="emp_code" id="empFormCode" placeholder="Auto-generated" readonly>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Photo</label>
                      <input type="file" class="form-control" name="photo" accept="image/*" id="empFormPhoto">
                      <img id="empPhotoPreview" class="hrms-photo-preview mt-2 d-none" alt="">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Full Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="name" id="empFormName" required>
                    </div>
                    <div class="col-md-4"><label class="form-label">First Name</label><input type="text" class="form-control" name="first_name" id="empFormFn"></div>
                    <div class="col-md-4"><label class="form-label">Last Name</label><input type="text" class="form-control" name="last_name" id="empFormLn"></div>
                    <div class="col-md-4"><label class="form-label">NIC / Passport</label><input type="text" class="form-control" name="nic_passport" id="empFormNic"></div>
                    <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" id="empFormDob"></div>
                    <div class="col-md-4"><label class="form-label">Gender</label><select class="form-select" name="gender" id="empFormGender"><option value="">—</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
                    <div class="col-md-4"><label class="form-label">Marital Status</label><select class="form-select" name="marital_status" id="empFormMarital"><option value="">—</option><option value="single">Single</option><option value="married">Married</option><option value="divorced">Divorced</option></select></div>
                    <div class="col-md-4"><label class="form-label">Nationality</label><input type="text" class="form-control" name="nationality" value="Sri Lankan" id="empFormNationality"></div>
                    <div class="col-md-4"><label class="form-label">Blood Group</label><input type="text" class="form-control" name="blood_group" id="empFormBlood"></div>
                    <div class="col-md-4"><label class="form-label">Religion</label><input type="text" class="form-control" name="religion" id="empFormReligion"></div>
                    <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2" id="empFormAddress"></textarea></div>
                    <div class="col-md-4"><label class="form-label">District</label><input type="text" class="form-control" name="district" id="empFormDistrict"></div>
                    <div class="col-md-4"><label class="form-label">Province</label><input type="text" class="form-control" name="province" id="empFormProvince"></div>
                    <div class="col-md-4"><label class="form-label">Postal Code</label><input type="text" class="form-control" name="postal_code" id="empFormPostal"></div>
                    <div class="col-md-4"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" id="empFormPhone"></div>
                    <div class="col-md-4"><label class="form-label">Mobile</label><input type="text" class="form-control" name="mobile" id="empFormMobile"></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="empFormEmail"></div>
                    <div class="col-md-6"><label class="form-label">Emergency Contact</label><input type="text" class="form-control" name="emergency_contact" id="empFormEcName"></div>
                    <div class="col-md-6"><label class="form-label">Emergency Phone</label><input type="text" class="form-control" name="emergency_phone" id="empFormEcPhone"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#empSecWork">Employment Information</button></h2>
              <div id="empSecWork" class="accordion-collapse collapse" data-bs-parent="#empAccordion">
                <div class="accordion-body row g-3">
                  <div class="col-md-4"><label class="form-label">Branch <span class="text-danger">*</span></label><select class="form-select" name="branch_id" id="empFormBranch" required></select></div>
                  <div class="col-md-4"><label class="form-label">Department</label><select class="form-select" name="department_id" id="empFormDept"></select></div>
                  <div class="col-md-4"><label class="form-label">Designation</label><select class="form-select" name="designation_id" id="empFormDesig"></select></div>
                  <div class="col-md-4"><label class="form-label">Position <span class="text-danger">*</span></label><input type="text" class="form-control" name="position" id="empFormPosition" required></div>
                  <div class="col-md-4"><label class="form-label">Job Title</label><input type="text" class="form-control" name="job_title" id="empFormJobTitle"></div>
                  <div class="col-md-4"><label class="form-label">Employee Type</label><select class="form-select" name="employment_type" id="empFormEmpType"><option value="permanent">Permanent</option><option value="contract">Contract</option><option value="temporary">Temporary</option><option value="intern">Intern</option></select></div>
                  <div class="col-md-4"><label class="form-label">Role</label><select class="form-select" name="role" id="empFormRole"><option value="">—</option><option value="admin">Admin</option><option value="manager">Manager</option><option value="driver">Driver</option><option value="clerk">Clerk</option><option value="mechanic">Mechanic</option><option value="accountant">Accountant</option></select></div>
                  <div class="col-md-4"><label class="form-label">Supervisor</label><select class="form-select" name="supervisor_id" id="empFormSupervisor"><option value="">—</option></select></div>
                  <div class="col-md-4"><label class="form-label">Joining Date</label><input type="date" class="form-control" name="join_date" id="empFormJoin"></div>
                  <div class="col-md-4"><label class="form-label">Confirmation Date</label><input type="date" class="form-control" name="confirmation_date" id="empFormConfirm"></div>
                  <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status" id="empFormStatus"><option value="active">Active</option><option value="inactive">Inactive</option><option value="suspended">Suspended</option></select></div>
                  <div class="col-md-4"><label class="form-label">License #</label><input type="text" class="form-control" name="license_number" id="empFormLicense"></div>
                  <div class="col-md-4"><label class="form-label">License Expiry</label><input type="date" class="form-control" name="license_expiry" id="empFormLicenseExp"></div>
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#empSecSalary">Salary Information</button></h2>
              <div id="empSecSalary" class="accordion-collapse collapse" data-bs-parent="#empAccordion">
                <div class="accordion-body row g-3">
                  <div class="col-md-3"><label class="form-label">Basic Salary</label><input type="number" step="0.01" class="form-control hrms-salary-inp" name="basic_salary" id="empFormBasic" value="0"></div>
                  <div class="col-md-3"><label class="form-label">Allowances</label><input type="number" step="0.01" class="form-control hrms-salary-inp" name="allowance_amount" id="empFormAllow" value="0"></div>
                  <div class="col-md-3"><label class="form-label">Overtime Rate</label><input type="number" step="0.01" class="form-control" name="overtime_rate" id="empFormOt" value="0"></div>
                  <div class="col-md-3"><label class="form-label">Tax</label><input type="number" step="0.01" class="form-control hrms-salary-inp" name="tax_amount" id="empFormTax" value="0"></div>
                  <div class="col-md-3"><label class="form-label">EPF (Employee)</label><input type="number" step="0.01" class="form-control" name="epf_employee" id="empFormEpfE" readonly></div>
                  <div class="col-md-3"><label class="form-label">EPF (Employer)</label><input type="number" step="0.01" class="form-control" name="epf_employer" id="empFormEpfEr" readonly></div>
                  <div class="col-md-3"><label class="form-label">ETF</label><input type="number" step="0.01" class="form-control" name="etf_amount" id="empFormEtf" readonly></div>
                  <div class="col-md-3"><label class="form-label fw-semibold">Net Salary</label><input type="number" step="0.01" class="form-control fw-bold" name="net_salary" id="empFormNet" readonly></div>
                  <div class="col-md-3"><label class="form-label">Bank Name</label><input type="text" class="form-control" name="bank_name" id="empFormBank"></div>
                  <div class="col-md-3"><label class="form-label">Bank Branch</label><input type="text" class="form-control" name="bank_branch" id="empFormBankBr"></div>
                  <div class="col-md-3"><label class="form-label">Account Number</label><input type="text" class="form-control" name="bank_account_no" id="empFormBankAcc"></div>
                  <div class="col-md-3"><label class="form-label">Account Holder</label><input type="text" class="form-control" name="bank_account_holder" id="empFormBankHolder"></div>
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#empSecSystem">System Access & Notes</button></h2>
              <div id="empSecSystem" class="accordion-collapse collapse" data-bs-parent="#empAccordion">
                <div class="accordion-body row g-3">
                  <div class="col-md-4"><label class="form-label">Username</label><input type="text" class="form-control" name="system_username" id="empFormUsername" autocomplete="off"></div>
                  <div class="col-md-4"><label class="form-label">Password</label><input type="password" class="form-control" name="system_password" id="empFormPassword" autocomplete="new-password" placeholder="Leave blank to skip"></div>
                  <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="2" id="empFormRemarks"></textarea></div>
                </div>
              </div>
            </div>
          </div>
          <div id="empFormError" class="alert alert-danger mt-3 d-none"></div>
        </div>
        <div class="modal-footer flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary w-100 w-sm-auto" id="empFormSubmit">Save Employee</button>
        </div>
      </form>
    </div>
  </div>
</div>
