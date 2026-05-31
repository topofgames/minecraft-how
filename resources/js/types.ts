export type ServerStatus = 'online' | 'maintenance' | 'offline';

export type MinecraftServer = {
  id: number;
  name: string;
  host: string;
  port: number;
  version: string;
  players: number;
  maxPlayers: number;
  mode: string;
  status: ServerStatus;
  country: string;
  description: string;
};

export type MotdSegment = {
  text: string;
  style: {
    color?: string;
    bold?: boolean;
    italic?: boolean;
    underlined?: boolean;
    strikethrough?: boolean;
  };
};

export type ServerLiveStatus = {
  id: number;
  online: boolean;
  latency: number | null;
  version?: string | null;
  players?: {
    online?: number | null;
    max?: number | null;
  };
  motd: {
    plain: string;
    segments: MotdSegment[];
  };
  favicon?: string | null;
  error?: string;
};
