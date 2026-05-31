import { Head } from '@inertiajs/react';
import { Copy, Gauge, RefreshCw, Search, Server, Users, type LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { MinecraftServer, MotdSegment, ServerLiveStatus } from '@/types';

type Props = {
  servers: MinecraftServer[];
};

const modes = ['All', 'Minigames', 'Survival', 'SMP', 'PvP', 'Skyblock'];

export default function ServerList({ servers }: Props) {
  const [query, setQuery] = useState('');
  const [mode, setMode] = useState('All');
  const [liveStatuses, setLiveStatuses] = useState<Record<number, ServerLiveStatus>>({});
  const [isRefreshing, setIsRefreshing] = useState(false);

  const refreshStatuses = async () => {
    setIsRefreshing(true);
    try {
      const response = await fetch('/api/servers/status', {
        headers: { Accept: 'application/json' },
      });
      const data = (await response.json()) as ServerLiveStatus[];
      setLiveStatuses(Object.fromEntries(data.map((status) => [status.id, status])));
    } finally {
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    void refreshStatuses();
  }, []);

  const filteredServers = useMemo(() => {
    return servers.filter((server) => {
      const matchesMode = mode === 'All' || server.mode === mode;
      const searchable = `${server.name} ${server.host} ${server.mode} ${server.country}`.toLowerCase();
      return matchesMode && searchable.includes(query.toLowerCase());
    });
  }, [mode, query, servers]);

  const onlinePlayers = servers.reduce((total, server) => total + server.players, 0);
  const onlineServers = servers.filter((server) => server.status === 'online').length;

  return (
    <>
      <Head title="Server List" />
      <main className="min-h-screen">
        <section className="border-b bg-card">
          <div className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
            <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
              <div className="max-w-2xl">
                <div className="mb-3 inline-flex items-center gap-2 rounded-md bg-muted px-3 py-1 text-sm font-medium text-muted-foreground">
                  <Server className="h-4 w-4" />
                  Minecraft Server List
                </div>
                <h1 className="text-3xl font-bold tracking-normal sm:text-4xl">
                  Find a Minecraft server to join
                </h1>
                <p className="mt-3 text-base text-muted-foreground">
                  A clean starter list with filters, live-looking server cards, and copyable IPs.
                </p>
              </div>
              <div className="grid grid-cols-2 gap-3 sm:min-w-72">
                <Stat icon={Server} label="Online" value={`${onlineServers}/${servers.length}`} />
                <Stat icon={Users} label="Players" value={onlinePlayers.toString()} />
              </div>
            </div>

            <div className="flex flex-col gap-3 md:flex-row">
              <div className="relative flex-1">
                <Search className="pointer-events-none absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  className="pl-9"
                  placeholder="Search by name, IP, mode, country"
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                />
              </div>
              <div className="flex flex-wrap gap-2">
                {modes.map((item) => (
                  <Button
                    key={item}
                    type="button"
                    variant={mode === item ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => setMode(item)}
                  >
                    {item}
                  </Button>
                ))}
                <Button type="button" variant="secondary" size="sm" onClick={refreshStatuses}>
                  <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                  Ping
                </Button>
              </div>
            </div>
          </div>
        </section>

        <section className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="grid gap-4 md:grid-cols-2">
            {filteredServers.map((server) => (
              <ServerCard key={server.id} liveStatus={liveStatuses[server.id]} server={server} />
            ))}
          </div>

          {filteredServers.length === 0 && (
            <Card className="mx-auto max-w-xl">
              <CardHeader>
                <CardTitle>No servers found</CardTitle>
                <CardDescription>Try another search term or mode filter.</CardDescription>
              </CardHeader>
            </Card>
          )}
        </section>

        <footer className="border-t bg-card">
          <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <span>Made by Minecraft.How</span>
            <a
              className="font-medium text-primary underline-offset-4 hover:underline"
              href="https://minecraft.how"
              rel="noreferrer"
              target="_blank"
            >
              minecraft.how
            </a>
          </div>
        </footer>
      </main>
    </>
  );
}

function ServerCard({
  server,
  liveStatus,
}: {
  server: MinecraftServer;
  liveStatus?: ServerLiveStatus;
}) {
  const players = liveStatus?.players?.online ?? server.players;
  const maxPlayers = liveStatus?.players?.max ?? server.maxPlayers;
  const load = maxPlayers > 0 ? Math.round((players / maxPlayers) * 100) : 0;
  const status = liveStatus ? (liveStatus.online ? 'online' : 'offline') : server.status;
  const statusVariant = status === 'online' ? 'default' : 'secondary';
  const version = liveStatus?.version ?? server.version;

  return (
    <Card>
      <CardHeader>
        <div className="flex items-start justify-between gap-3">
          <div>
            <CardTitle>{server.name}</CardTitle>
            <CardDescription className="mt-2">
              {liveStatus?.motd.segments.length ? (
                <Motd segments={liveStatus.motd.segments} />
              ) : (
                server.description
              )}
            </CardDescription>
          </div>
          <Badge variant={statusVariant}>{status}</Badge>
        </div>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex flex-wrap gap-2">
          <Badge variant="outline">{server.mode}</Badge>
          <Badge variant="outline">{version}</Badge>
          <Badge variant="muted">{server.country}</Badge>
          {liveStatus?.latency !== null && liveStatus?.latency !== undefined && (
            <Badge variant="muted">
              <Gauge className="mr-1 h-3 w-3" />
              {liveStatus.latency} ms
            </Badge>
          )}
        </div>

        <div className="rounded-md border bg-muted/50 p-3">
          <div className="mb-2 flex items-center justify-between gap-3 text-sm">
            <span className="font-medium">Players</span>
            <span className="text-muted-foreground">
              {players}/{maxPlayers}
            </span>
          </div>
          <div className="h-2 rounded-full bg-border">
            <div className="h-2 rounded-full bg-primary" style={{ width: `${load}%` }} />
          </div>
        </div>

        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <code className="rounded-md bg-foreground px-3 py-2 text-sm text-background">
            {server.port === 25565 ? server.host : `${server.host}:${server.port}`}
          </code>
          <Button
            type="button"
            variant="secondary"
            onClick={() => navigator.clipboard?.writeText(server.host)}
          >
            <Copy className="h-4 w-4" />
            Copy IP
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function Motd({ segments }: { segments: MotdSegment[] }) {
  return (
    <span className="font-mono">
      {segments.map((segment, index) => (
        <span
          key={`${segment.text}-${index}`}
          style={{
            color: segment.style.color,
            fontWeight: segment.style.bold ? 700 : undefined,
            fontStyle: segment.style.italic ? 'italic' : undefined,
            textDecoration:
              segment.style.underlined && segment.style.strikethrough
                ? 'underline line-through'
                : segment.style.underlined
                  ? 'underline'
                  : segment.style.strikethrough
                    ? 'line-through'
                    : undefined,
          }}
        >
          {segment.text}
        </span>
      ))}
    </span>
  );
}

function Stat({
  icon: Icon,
  label,
  value,
}: {
  icon: LucideIcon;
  label: string;
  value: string;
}) {
  return (
    <div className="rounded-lg border bg-background p-4">
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <Icon className="h-4 w-4" />
        {label}
      </div>
      <div className="mt-2 text-2xl font-bold">{value}</div>
    </div>
  );
}
