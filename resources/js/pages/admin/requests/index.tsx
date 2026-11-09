import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as adminRequests, run as runRequest } from '@/routes/admin/requests';

interface HeroSummary {
    id: number;
    slug: string;
    status: string;
}

interface SearchRequestRow {
    id: number;
    query: string;
    hits_count: number;
    status: string;
    last_searched_at: string | null;
    review_note: string | null;
    hero: HeroSummary | null;
}

interface Props {
    requests: {
        data: SearchRequestRow[];
        total: number;
        current_page: number;
        last_page: number;
    };
    filters: { status: string };
    counts: Record<string, number>;
}

const STATUSES = ['pending', 'processing', 'resolved', 'rejected'] as const;

const STATUS_LABELS: Record<string, string> = {
    pending: 'En attente',
    processing: 'En cours',
    resolved: 'Traitees',
    rejected: 'Rejetees',
};

/** Depuis quand, en mots. Evite une dependance pour quatre cas. */
function timeAgo(iso: string | null): string {
    if (!iso) return '—';

    const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);

    if (seconds < 60) return "a l'instant";
    if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`;
    if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)} h`;

    return `il y a ${Math.floor(seconds / 86400)} j`;
}

export default function RequestsIndex({ requests, filters, counts }: Props) {
    const [openRow, setOpenRow] = useState<number | null>(null);
    const [context, setContext] = useState('');
    const [sending, setSending] = useState(false);

    const processing = requests.data.filter((row) => row.status === 'processing').length;

    /**
     * Le pipeline tourne en file d'attente : rien ne previent le navigateur
     * quand il se termine. Tant qu'une recherche est en cours, on redemande
     * la page a intervalle regulier. Le sondage s'arrete de lui-meme des que
     * plus aucune ligne n'est « en cours », sans rien a desactiver a la main.
     */
    useEffect(() => {
        if (processing === 0) return;

        const timer = setInterval(() => {
            router.reload({ only: ['requests', 'counts'] });
        }, 5000);

        return () => clearInterval(timer);
    }, [processing]);

    function launch(id: number) {
        setSending(true);

        router.post(
            runRequest(id).url,
            { context: context.trim() || null },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSending(false);
                    setOpenRow(null);
                    setContext('');
                },
            },
        );
    }

    return (
        <>
            <Head title="Demandes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Demandes de recherche</h1>
                    <p className="text-sm text-muted-foreground">
                        Les figures cherchees par les visiteurs et absentes de la base.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    {STATUSES.map((status) => (
                        <Link
                            key={status}
                            href={`${adminRequests().url}?status=${status}`}
                            preserveScroll
                            className={[
                                'rounded-lg px-3 py-1.5 text-sm transition-colors',
                                filters.status === status
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground hover:bg-muted/70',
                            ].join(' ')}
                        >
                            {STATUS_LABELS[status]}
                            {counts[status] ? (
                                <span className="ml-1.5 opacity-70">{counts[status]}</span>
                            ) : null}
                        </Link>
                    ))}
                </div>

                {requests.data.length === 0 ? (
                    <div className="rounded-xl border border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                        Aucune demande dans cette file.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                                <tr className="text-left">
                                    <th className="px-4 py-2.5 font-medium">Requete</th>
                                    <th className="px-4 py-2.5 font-medium">Demandes</th>
                                    <th className="px-4 py-2.5 font-medium">Derniere</th>
                                    <th className="px-4 py-2.5 font-medium">Fiche</th>
                                    <th className="px-4 py-2.5" />
                                </tr>
                            </thead>

                            <tbody>
                                {requests.data.map((request) => (
                                    <RequestRow
                                        key={request.id}
                                        request={request}
                                        isOpen={openRow === request.id}
                                        sending={sending}
                                        context={context}
                                        onContextChange={setContext}
                                        onToggle={() => {
                                            setOpenRow(openRow === request.id ? null : request.id);
                                            setContext('');
                                        }}
                                        onLaunch={() => launch(request.id)}
                                    />
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {requests.last_page > 1 && (
                    <p className="text-sm text-muted-foreground">
                        Page {requests.current_page} sur {requests.last_page} — {requests.total} demandes.
                    </p>
                )}
            </div>
        </>
    );
}

interface RowProps {
    request: SearchRequestRow;
    isOpen: boolean;
    sending: boolean;
    context: string;
    onContextChange: (value: string) => void;
    onToggle: () => void;
    onLaunch: () => void;
}

function RequestRow({
    request,
    isOpen,
    sending,
    context,
    onContextChange,
    onToggle,
    onLaunch,
}: RowProps) {
    return (
        <>
            <tr className="border-b border-sidebar-border/40 last:border-0">
                <td className="px-4 py-3 font-medium">
                    {request.query}
                    {request.review_note && (
                        <p className="mt-1 text-xs text-muted-foreground">
                            Echec precedent : {request.review_note}
                        </p>
                    )}
                </td>

                <td className="px-4 py-3 tabular-nums">{request.hits_count}</td>

                <td className="px-4 py-3 text-muted-foreground">
                    {timeAgo(request.last_searched_at)}
                </td>

                <td className="px-4 py-3">
                    {request.hero ? (
                        <span className="flex items-center gap-2 text-muted-foreground">
                            {request.hero.slug}
                            <Badge variant="secondary">{request.hero.status}</Badge>
                        </span>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    )}
                </td>

                <td className="px-4 py-3 text-right">
                    {request.status === 'processing' ? (
                        <Badge variant="secondary">Recherche en cours…</Badge>
                    ) : request.status === 'pending' ? (
                        <Button
                            size="sm"
                            variant={isOpen ? 'secondary' : 'default'}
                            onClick={onToggle}
                        >
                            {isOpen ? 'Annuler' : 'Lancer'}
                        </Button>
                    ) : null}
                </td>
            </tr>

            {isOpen && (
                <tr className="border-b border-sidebar-border/40 bg-muted/30">
                    <td colSpan={5} className="px-4 py-3">
                        <div className="flex flex-col gap-2">
                            <label className="text-xs text-muted-foreground">
                                Precision ajoutee a la requete web. Un contexte precis change
                                nettement la qualite des sources trouvees.
                            </label>

                            <div className="flex gap-2">
                                <Input
                                    autoFocus
                                    value={context}
                                    onChange={(event) => onContextChange(event.target.value)}
                                    placeholder="ecrivain marocain, prix Goncourt"
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter' && !sending) onLaunch();
                                    }}
                                />
                                <Button onClick={onLaunch} disabled={sending}>
                                    {sending ? 'Envoi…' : 'Lancer la recherche'}
                                </Button>
                            </div>
                        </div>
                    </td>
                </tr>
            )}
        </>
    );
}

RequestsIndex.layout = {
    breadcrumbs: [{ title: 'Demandes', href: adminRequests() }],
};