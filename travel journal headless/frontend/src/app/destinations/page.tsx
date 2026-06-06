import type { Metadata } from 'next';
import DestinationCard from '@/components/DestinationCard';
import { getAllDestinations, type Destination } from '@/lib/api';
import styles from './page.module.css';

export const metadata: Metadata = {
  title: 'Destinations | Wanderlust Journal',
  description: 'Explore all the incredible destinations from around the world.',
};

export default async function DestinationsPage() {
  let destinations: Destination[] = [];

  try {
    destinations = await getAllDestinations();
  } catch (error) {
    console.error('Failed to fetch destinations:', error);
  }

  return (
    <section className={styles.page}>
      <div className={styles.container}>
        <div className={styles.pageHeader}>
          <h1 className={styles.pageTitle}>Destinations</h1>
          <p className={styles.pageSubtitle}>
            Every great adventure begins with a place that calls to your soul
          </p>
          <div className={styles.divider} />
        </div>

        {destinations.length > 0 ? (
          <div className="card-grid">
            {destinations.map((destination, index) => (
              <div
                key={destination.databaseId}
                className="animate-fade-in-up"
                style={{ animationDelay: `${index * 0.1}s` }}
              >
                <DestinationCard destination={destination} />
              </div>
            ))}
          </div>
        ) : (
          <div className={styles.emptyState}>
            <span className={styles.emptyIcon}>🗺️</span>
            <p>No destinations found. Add some in your WordPress admin!</p>
          </div>
        )}
      </div>
    </section>
  );
}
