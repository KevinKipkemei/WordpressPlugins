'use client';

import { useState, useEffect } from 'react';
import TripCard from '@/components/TripCard';
import FilterBar from '@/components/FilterBar';
import styles from './page.module.css';
import type { Trip } from '@/lib/api';

const DIFFICULTY_FILTERS = [
  { value: 'all', label: 'All Trips' },
  { value: 'Easy', label: '🟢 Easy' },
  { value: 'Moderate', label: '🟠 Moderate' },
  { value: 'Challenging', label: '🔴 Challenging' },
];

export default function TripsPageClient({ trips }: { trips: Trip[] }) {
  const [activeFilter, setActiveFilter] = useState('all');
  const [filteredTrips, setFilteredTrips] = useState<Trip[]>(trips);

  useEffect(() => {
    if (activeFilter === 'all') {
      setFilteredTrips(trips);
    } else {
      setFilteredTrips(
        trips.filter((trip) => trip.difficulty === activeFilter)
      );
    }
  }, [activeFilter, trips]);

  return (
    <>
      <FilterBar
        filters={DIFFICULTY_FILTERS}
        activeFilter={activeFilter}
        onFilterChange={setActiveFilter}
      />

      {filteredTrips.length > 0 ? (
        <div className="card-grid">
          {filteredTrips.map((trip, index) => (
            <div
              key={trip.databaseId}
              className="animate-fade-in-up"
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <TripCard trip={trip} />
            </div>
          ))}
        </div>
      ) : (
        <div className={styles.emptyState}>
          <span className={styles.emptyIcon}>🧭</span>
          <p>No trips match this filter.</p>
        </div>
      )}
    </>
  );
}
