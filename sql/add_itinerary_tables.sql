CREATE TABLE IF NOT EXISTS itinerary (
    itinerary_id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    day_number INT NOT NULL,
    itinerary_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_itinerary_trip
        FOREIGN KEY (trip_id) REFERENCES trip(trip_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS itinerary_stop (
    stop_id INT AUTO_INCREMENT PRIMARY KEY,
    itinerary_id INT NOT NULL,
    stop_order INT NOT NULL,
    stop_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stop_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itinerary(itinerary_id)
        ON DELETE CASCADE
);
