'use client';

import styles from './FilterBar.module.css';

interface Filter {
  value: string;
  label: string;
}

interface FilterBarProps {
  filters: Filter[];
  activeFilter: string;
  onFilterChange: (value: string) => void;
}

export default function FilterBar({
  filters,
  activeFilter,
  onFilterChange,
}: FilterBarProps) {
  return (
    <div className={styles.wrapper}>
      <div className={styles.scrollContainer}>
        <div className={styles.pillRow}>
          {filters.map((filter) => (
            <button
              key={filter.value}
              className={`${styles.pill} ${
                activeFilter === filter.value ? styles.pillActive : ''
              }`}
              onClick={() => onFilterChange(filter.value)}
              aria-pressed={activeFilter === filter.value}
            >
              {filter.label}
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
