import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { index as adminHeroes } from '@/routes/admin/heroes';

interface Translation {
    locale: string;
    name: string | null;
    summary: string | null;
    biography: string | null;
}

interface HeroRow {
    id: number;
    slug: string;
    status: string;
    birth_year: number | null;
    death_year: number | null;
    is_alive: boolean;
    translations: Translation[];
}

interface Props {
    heroes: {
        data: HeroRow[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

const LOCALES = ['fr', 'ar', 'zgh', 'en'] as const;

const LOCALE_LABELS: Record<string, string> = {
    fr: 'FR',
    ar: 'AR',
    zgh: 'ⵣ',
    en: 'EN',
};

/** Le nom dans la langue demandee, avec repli sur le francais. */
function nameOf(hero: HeroRow): string {
    const translations = hero.translations ?? [];

    return (
        translations.find((t) => t.locale === 'fr')?.name ??
        translations.find((t) => t.name)?.name ??
        hero.slug
    );
}

function years(hero: HeroRow): string {
    if (!hero.birth_year) return '—';

    return hero.is_alive
        ? `${hero.birth_year} –`
        : `${hero.birth_year} – ${hero.death_year ?? '?'}`;
}

export default function HeroesIndex({ heroes }: Props) {
    return (
        <>
            <Head title="Fiches" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Fiches</h1>
                    <p className="text-sm text-muted-foreground">
                        {heroes.total} figures, des brouillons aux fiches publiees.
                    </p>
                </div>

                {heroes.data.length === 0 ? (
                    <div className="rounded-xl border border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                        Aucune fiche pour le moment.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                                <tr className="text-left">
                                    <th className="px-4 py-2.5 font-medium">Nom</th>
                                    <th className="px-4 py-2.5 font-medium">Dates</th>
                                    <th className="px-4 py-2.5 font-medium">Statut</th>
                                    <th className="px-4 py-2.5 font-medium">Langues</th>
                                </tr>
                            </thead>

                            <tbody>
                                {heroes.data.map((hero) => (
                                    <tr
                                        key={hero.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{nameOf(hero)}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {hero.slug}
                                            </div>
                                        </td>

                                        <td className="px-4 py-3 tabular-nums text-muted-foreground">
                                            {years(hero)}
                                        </td>

                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    hero.status === 'published'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {hero.status}
                                            </Badge>
                                        </td>

                                        <td className="px-4 py-3">
                                            <LanguageDots hero={hero} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {heroes.last_page > 1 && (
                    <p className="text-sm text-muted-foreground">
                        Page {heroes.current_page} sur {heroes.last_page}.
                    </p>
                )}
            </div>
        </>
    );
}

/**
 * Ce qui manque, d'un coup d'oeil.
 *
 * Une langue compte comme presente si elle a un nom et un resume ; la
 * biographie est signalee a part, car c'est le champ qui echoue le plus
 * souvent a l'extraction et celui qu'on reprend a la main.
 */
function LanguageDots({ hero }: { hero: HeroRow }) {
    const translations = hero.translations ?? [];

    return (
        <div className="flex gap-1.5">
            {LOCALES.map((locale) => {
                const translation = translations.find((t) => t.locale === locale);
                const hasText = Boolean(translation?.name && translation?.summary);
                const hasBio = Boolean(translation?.biography);

                return (
                    <span
                        key={locale}
                        title={
                            hasText
                                ? hasBio
                                    ? 'Nom, resume et biographie'
                                    : 'Nom et resume, sans biographie'
                                : 'Absente'
                        }
                        className={[
                            'rounded px-1.5 py-0.5 text-xs font-medium',
                            hasText
                                ? hasBio
                                    ? 'bg-primary/15 text-primary'
                                    : 'bg-muted text-foreground'
                                : 'bg-transparent text-muted-foreground/40',
                        ].join(' ')}
                    >
                        {LOCALE_LABELS[locale]}
                    </span>
                );
            })}
        </div>
    );
}

HeroesIndex.layout = {
    breadcrumbs: [{ title: 'Fiches', href: adminHeroes() }],
};