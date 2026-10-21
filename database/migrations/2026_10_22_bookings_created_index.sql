-- Reports page: faster "bookings made between two dates"
ALTER TABLE bookings ADD KEY idx_bookings_created (created_at);
