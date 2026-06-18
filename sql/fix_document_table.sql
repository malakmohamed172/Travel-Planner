-- travel_planner: document table aligned with uploads (trip + member linkage)
-- Run once. Skip FK lines if constraints already exist.

ALTER TABLE document
  ADD COLUMN trip_id int(11) NULL DEFAULT NULL AFTER document_id;

ALTER TABLE document
  ADD COLUMN member_id int(11) NULL DEFAULT NULL AFTER trip_id;

ALTER TABLE document ADD KEY idx_document_trip (trip_id);
ALTER TABLE document ADD KEY idx_document_member (member_id);

ALTER TABLE document
  ADD CONSTRAINT document_fk_trip FOREIGN KEY (trip_id) REFERENCES trip(trip_id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE document
  ADD CONSTRAINT document_fk_member FOREIGN KEY (member_id) REFERENCES member(member_id)
    ON DELETE SET NULL ON UPDATE CASCADE;
