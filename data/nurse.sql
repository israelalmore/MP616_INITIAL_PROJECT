INSERT INTO nurse (id, user, password) VALUES
(1, 'nurse1', 'n1pass'),
(2, 'nurse2', 'n2pass'),
(3, 'nurse3', 'n3pass'),
(4, 'nurse4', 'n4pass'),
(5, 'nurse5', 'n5pass'),
(6, 'nurse6', 'n6pass'),
(7, 'nurse7', 'n7pass'),
(8, 'nurse8', 'n8pass'),
(9, 'nurse9', 'n9pass'),
(10, 'nurse10', 'n10pass');

INSERT INTO nurse_credential (
    id,
    nurse_id,
    license_number,
    certification,
    issuing_body,
    issue_date,
    expiration_date
) VALUES
(1, 1, 'RN-12345', 'Basic Life Support', 'American Red Cross', '2025-01-01', '2027-01-01'),
(2, 2, 'RN-67890', 'Advanced Cardiovascular Life Support', 'American Heart Association', '2025-03-01', '2027-03-01'),
(3, 3, 'RN-30471', 'Pediatric Advanced Life Support', 'American Heart Association', '2025-04-12', '2027-04-12'),
(4, 4, 'RN-58263', 'Emergency Nursing', 'Board of Certification for Emergency Nursing', '2024-09-08', '2028-09-08'),
(5, 5, 'RN-91725', 'Certified Medical-Surgical Registered Nurse', 'American Nurses Credentialing Center', '2025-06-20', '2030-06-20'),
(6, 6, 'RN-46318', 'Critical Care Registered Nurse', 'American Association of Critical-Care Nurses', '2024-11-15', '2027-11-15'),
(7, 7, 'RN-75042', 'Oncology Certified Nurse', 'Oncology Nursing Certification Corporation', '2025-02-03', '2028-02-03'),
(8, 8, 'RN-21836', 'Neonatal Intensive Care Nursing', 'National Certification Corporation', '2025-05-14', '2028-05-14'),
(9, 9, 'RN-63914', 'Certified Registered Nurse Anesthetist', 'National Board of Certification and Recertification for Nurse Anesthetists', '2024-12-02', '2028-12-02'),
(10, 10, 'RN-47520', 'Certified Nurse Midwife', 'American Midwifery Certification Board', '2025-07-19', '2029-07-19');