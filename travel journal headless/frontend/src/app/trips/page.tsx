import type { Metadata } from 'next';
import { getAllTrips, type Trip } from '@/lib/api';
import TripsPageClient from './TripsPageClient';
import styles from './page.module.css';

export const metadata: Metadata = {
  title: 'Trips | Wanderlust Journal',
  description: 'Browse all travel adventures — filter by difficulty, explore trip details.',
};

export default async function TripsPage() {
  let trips: Trip[] = [];

  try {
    trips = await getAllTrips();
  } catch (error) {
    console.error('Failed to fetch trips:', error);
  }

  return (
    <section className={styles.page}>
      <div className={styles.container}>
        <div className={styles.pageHeader}>
          <h1 className={styles.pageTitle}>Trips & Adventures</h1>
          <p className={styles.pageSubtitle}>
            Every journey has a story — here are ours
          </p>
          <div className={styles.divider} />
        </div>

        <TripsPageClient trips={trips} />
      </div>
    </section>
  );
}
