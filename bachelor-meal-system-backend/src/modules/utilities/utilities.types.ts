export interface UtilityEntry {
  personId: number;
  wifi: number;
  current: number;
  gas: number;
  other: number;
}

export interface FundEntry {
  personId: number;
  collected: number;
}

export interface SharedUtilityDto {
  type: string;
  amount: number;
  lastReset: Date;
  daysRunning: number;
}
