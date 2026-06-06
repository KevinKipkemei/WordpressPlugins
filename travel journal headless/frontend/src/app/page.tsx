import Hero from '@/components/Hero';
import TripCard from '@/components/TripCard';
import DestinationCard from '@/components/DestinationCard';
import { getFeaturedTrips, getAllDestinations, type Trip, type Destination } from '@/lib/api';
import styles from './page.module.css';

export default async function HomePage() {
  let featuredTrips: Trip[] = [];
  let destinations: Destination[] = [];

  try {
    [featuredTrips, destinations] = await Promise.all([
      getFeaturedTrips(3),
      getAllDestinations(),
    ]);
  } catch (error) {
    console.error('Failed to fetch homepage data:', error);
  }

  return (
    <>
      <Hero />

      {/* Featured Trips Section */}
      <section className={styles.section} id="featured-trips">
        <div className={styles.container}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Recent Adventures</h2>
            <p className={styles.sectionSubtitle}>
              The latest stories from the trail
            </p>
          </div>

          {featuredTrips.length > 0 ? (
            <div className="card-grid">
              {featuredTrips.map((trip, index) => (
                <div
                  key={trip.databaseId}
                  className="animate-fade-in-up"
                  style={{ animationDelay: `${index * 0.15}s` }}
                >
                  <TripCard trip={trip} />
                </div>
              ))}
            </div>
          ) : (
            <p className={styles.emptyMessage}>
              No trips yet. Add some in your WordPress admin!
            </p>
          )}
        </div>
      </section>

      {/* Destinations Section */}
      <section className={`${styles.section} ${styles.sectionAlt}`} id="destinations">
        <div className={styles.container}>
          <div className={styles.sectionHeader}>
            <h2 className={styles.sectionTitle}>Explore Destinations</h2>
            <p className={styles.sectionSubtitle}>
              Discover extraordinary places around the globe
            </p>
          </div>

          {destinations.length > 0 ? (
            <div className="card-grid">
              {destinations.map((destination, index) => (
                <div
                  key={destination.databaseId}
                  className="animate-fade-in-up"
                  style={{ animationDelay: `${index * 0.15}s` }}
                >
                  <DestinationCard destination={destination} />
                </div>
              ))}
            </div>
          ) : (
            <p className={styles.emptyMessage}>
              No destinations yet. Add some in your WordPress admin!
            </p>
          )}
        </div>
      </section>
    </>
  );
}
