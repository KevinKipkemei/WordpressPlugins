import { notFound } from 'next/navigation';
import type { Metadata } from 'next';
import Link from 'next/link';
import { getTripBySlug, getAllTrips, formatTravelDate, formatCost } from '@/lib/api';
import styles from './page.module.css';

interface PageProps {
  params: Promise<{ slug: string }>;
}

export async function generateStaticParams() {
  try {
    const trips = await getAllTrips();
    return trips.map((t) => ({ slug: t.slug }));
  } catch {
    return [];
  }
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  try {
    const trip = await getTripBySlug(slug);
    return {
      title: trip ? `${trip.title} | Wanderlust Journal` : 'Trip Not Found',
      description: trip?.excerpt?.replace(/<[^>]+>/g, '') || '',
    };
  } catch {
    return { title: 'Trip | Wanderlust Journal' };
  }
}

export default async function TripDetailPage({ params }: PageProps) {
  const { slug } = await params;
  let trip;

  try {
    trip = await getTripBySlug(slug);
  } catch (error) {
    console.error('Failed to fetch trip:', error);
    notFound();
  }

  if (!trip) {
    notFound();
  }

  const difficultyClass =
    trip.difficulty === 'Easy'
      ? styles.badgeEasy
      : trip.difficulty === 'Moderate'
      ? styles.badgeModerate
      : styles.badgeChallenging;

  return (
    <article className={styles.page}>
      {/* Hero */}
      <div className={styles.hero}>
        {trip.featuredImage ? (
          <img
            src={trip.featuredImage.node.sourceUrl}
            alt={trip.featuredImage.node.altText || trip.title}
            className={styles.heroImage}
          />
        ) : (
          <div className={styles.heroGradient} />
        )}
        <div className={styles.heroOverlay}>
          <div className={styles.heroContent}>
            <Link href="/trips" className={styles.backLink}>
              ← All Trips
            </Link>
            <h1 className={styles.heroTitle}>{trip.title}</h1>
            {trip.destination && (
              <Link
                href={`/destinations/${trip.destination.slug}`}
                className={styles.destinationLink}
              >
                📍 {trip.destination.title}, {trip.destination.country}
              </Link>
            )}
          </div>
        </div>
      </div>

      <div className={styles.container}>
        {/* Stats Bar */}
        <div className={styles.statsBar}>
          <div className={styles.stat}>
            <span className={styles.statIcon}>📅</span>
            <div>
              <span className={styles.statLabel}>Date</span>
              <span className={styles.statValue}>{formatTravelDate(trip.travelDate)}</span>
            </div>
          </div>
          <div className={styles.stat}>
            <span className={styles.statIcon}>⏱️</span>
            <div>
              <span className={styles.statLabel}>Duration</span>
              <span className={styles.statValue}>{trip.durationDays} days</span>
            </div>
          </div>
          <div className={styles.stat}>
            <span className={styles.statIcon}>💰</span>
            <div>
              <span className={styles.statLabel}>Cost</span>
              <span className={styles.statValue}>{formatCost(trip.costUsd)}</span>
            </div>
          </div>
          <div className={styles.stat}>
            <span className={styles.statIcon}>⛰️</span>
            <div>
              <span className={styles.statLabel}>Difficulty</span>
              <span className={`${styles.statValue} ${difficultyClass}`}>
                {trip.difficulty}
              </span>
            </div>
          </div>
        </div>

        {/* Content */}
        <section className={styles.contentSection}>
          <div
            className={styles.prose}
            dangerouslySetInnerHTML={{ __html: trip.content || '' }}
          />
        </section>

        {/* Back navigation */}
        <div className={styles.bottomNav}>
          <Link href="/trips" className={styles.bottomLink}>
            ← Back to All Trips
          </Link>
          {trip.destination && (
            <Link
              href={`/destinations/${trip.destination.slug}`}
              className={styles.bottomLink}
            >
              More in {trip.destination.title} →
            </Link>
          )}
        </div>
      </div>
    </article>
  );
}
