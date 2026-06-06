import { notFound } from 'next/navigation';
import type { Metadata } from 'next';
import Link from 'next/link';
import TripCard from '@/components/TripCard';
import { getDestinationBySlug, getAllDestinations } from '@/lib/api';
import styles from './page.module.css';

interface PageProps {
  params: Promise<{ slug: string }>;
}

export async function generateStaticParams() {
  try {
    const destinations = await getAllDestinations();
    return destinations.map((d) => ({ slug: d.slug }));
  } catch {
    return [];
  }
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  try {
    const destination = await getDestinationBySlug(slug);
    return {
      title: destination ? `${destination.title} | Wanderlust Journal` : 'Destination Not Found',
      description: destination?.excerpt?.replace(/<[^>]+>/g, '') || '',
    };
  } catch {
    return { title: 'Destination | Wanderlust Journal' };
  }
}

export default async function DestinationDetailPage({ params }: PageProps) {
  const { slug } = await params;
  let destination;

  try {
    destination = await getDestinationBySlug(slug);
  } catch (error) {
    console.error('Failed to fetch destination:', error);
    notFound();
  }

  if (!destination) {
    notFound();
  }

  const trips = destination.trips || [];

  return (
    <article className={styles.page}>
      {/* Hero Banner */}
      <div className={styles.hero}>
        {destination.featuredImage ? (
          <img
            src={destination.featuredImage.node.sourceUrl}
            alt={destination.featuredImage.node.altText || destination.title}
            className={styles.heroImage}
          />
        ) : (
          <div className={styles.heroGradient} />
        )}
        <div className={styles.heroOverlay}>
          <div className={styles.heroContent}>
            <Link href="/destinations" className={styles.backLink}>
              ← All Destinations
            </Link>
            <h1 className={styles.heroTitle}>{destination.title}</h1>
            <div className={styles.metaRow}>
              <span className={styles.metaBadge}>📍 {destination.country}</span>
              {destination.bestTimeToVisit && (
                <span className={styles.metaBadge}>🗓️ Best: {destination.bestTimeToVisit}</span>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className={styles.container}>
        {/* Content */}
        <section className={styles.contentSection}>
          <div
            className={styles.prose}
            dangerouslySetInnerHTML={{ __html: destination.content || '' }}
          />
        </section>

        {/* Trips at this destination */}
        {trips.length > 0 && (
          <section className={styles.tripsSection}>
            <h2 className={styles.tripsTitle}>
              Adventures in {destination.title}
              <span className={styles.tripCount}>{trips.length}</span>
            </h2>
            <div className="card-grid">
              {trips.map((trip, index) => (
                <div
                  key={trip.databaseId}
                  className="animate-fade-in-up"
                  style={{ animationDelay: `${index * 0.1}s` }}
                >
                  <TripCard trip={trip} />
                </div>
              ))}
            </div>
          </section>
        )}
      </div>
    </article>
  );
}
