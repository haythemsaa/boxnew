import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import customerService from '../services/customerService';
import contractService from '../services/contractService';
import Card from '../components/Card';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const DashboardScreen = ({ navigation }) => {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [statistics, setStatistics] = useState(null);
  const [contracts, setContracts] = useState([]);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [statsData, contractsData] = await Promise.all([
        customerService.getStatistics(),
        contractService.getContracts(),
      ]);

      setStatistics(statsData.statistics);
      setContracts(contractsData.contracts || contractsData);
    } catch (error) {
      console.error('Error loading dashboard data:', error);
      Alert.alert('Erreur', 'Impossible de charger les données');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadData();
  };

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        style={styles.scrollView}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {/* Header */}
        <View style={styles.header}>
          <Text style={styles.greeting}>Bonjour,</Text>
          <Text style={styles.userName}>{user?.name || 'Client'}</Text>
        </View>

        {/* Statistics Cards */}
        {statistics && (
          <View style={styles.statsContainer}>
            <Card style={styles.statCard}>
              <Text style={styles.statValue}>{statistics.active_contracts}</Text>
              <Text style={styles.statLabel}>Contrats actifs</Text>
            </Card>

            <Card style={styles.statCard}>
              <Text style={styles.statValue}>{statistics.pending_invoices}</Text>
              <Text style={styles.statLabel}>Factures en attente</Text>
            </Card>
          </View>
        )}

        {/* Active Contracts */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Mes contrats</Text>

          {contracts.length === 0 ? (
            <Card>
              <Text style={styles.emptyText}>Aucun contrat actif</Text>
            </Card>
          ) : (
            contracts.map((contract) => (
              <Card
                key={contract.id}
                style={styles.contractCard}
              >
                <View style={styles.contractHeader}>
                  <Text style={styles.contractNumber}>{contract.contract_number}</Text>
                  <StatusBadge
                    status={contract.status}
                    label={contract.status_label}
                  />
                </View>

                {contract.box && (
                  <>
                    <Text style={styles.boxNumber}>Box {contract.box.number}</Text>
                    <Text style={styles.boxDetails}>
                      {contract.box.volume}m³ • {contract.box.site}
                    </Text>
                  </>
                )}

                <View style={styles.contractFooter}>
                  <Text style={styles.contractPrice}>
                    {contract.total_monthly_amount?.toFixed(2)} € / mois
                  </Text>
                </View>
              </Card>
            ))
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
  },
  header: {
    padding: 20,
    backgroundColor: COLORS.primary,
  },
  greeting: {
    fontSize: 16,
    color: COLORS.white,
    opacity: 0.9,
  },
  userName: {
    fontSize: 28,
    fontWeight: 'bold',
    color: COLORS.white,
    marginTop: 4,
  },
  statsContainer: {
    flexDirection: 'row',
    padding: 16,
    gap: 12,
  },
  statCard: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: 32,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  statLabel: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginTop: 4,
    textAlign: 'center',
  },
  section: {
    padding: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  contractCard: {
    marginBottom: 12,
  },
  contractHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  contractNumber: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
  },
  boxNumber: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  boxDetails: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 12,
  },
  contractFooter: {
    borderTopWidth: 1,
    borderTopColor: COLORS.divider,
    paddingTop: 12,
  },
  contractPrice: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.primary,
  },
  emptyText: {
    textAlign: 'center',
    color: COLORS.text.secondary,
    fontSize: 14,
  },
});

export default DashboardScreen;
