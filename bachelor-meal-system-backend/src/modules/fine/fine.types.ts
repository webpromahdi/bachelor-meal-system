export interface FineEntryInput {
  personId: number;
  count?: number;
  fineCount?: number;
  amount?: number;
  reason?: string;
}

export interface SaveFinesPayload {
  date: string;
  counts?: Record<string | number, number | string> | FineEntryInput[];
  fineCounts?: Record<string | number, number | string> | FineEntryInput[];
  amounts?: Record<string | number, number | string>;
  reason?: string;
  fines?: FineEntryInput[];
}

export interface FineSummaryItem {
  personId: number;
  personName: string;
  totalCount: number;
  totalAmount: number;
}
