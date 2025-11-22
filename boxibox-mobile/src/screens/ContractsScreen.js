import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import contractService from '../services/contractService';
import Card from '../components/Card';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const ContractsScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [contracts, setContracts] = useState([]);

  useEffect(() => {
    loadContracts();
  }, []);

  const loadContracts = async () => {
    try {
      const data = await contractService.getContracts();
      setContracts(data.contracts || data);
    } catch (error) {
      console.error('Error loading contracts:', error);
      Alert.alert('Erreur', 'Impossible de charger les contrats');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadContracts();
  };

  const renderContract = ({ item }) => (
    <TouchableOpacity
      onPress={() => navigation.navigate('ContractDetails', { contractId: item.id })}
    >
      <Card>
        <View style={styles.contractHeader}>
          <Text style={styles.contractNumber}>{item.contract_number}</Text>
          <StatusBadge status={item.status} label={item.status_label} />
        </View>

        {item.box && (
          <>
            <Text style={styles.boxNumber}>Box {item.box.number}</Text>
            <Text style={styles.boxDetails}>
              {item.box.volume}m³ • {item.box.surface}m²
            </Text>
            <Text style={styles.siteInfo}>{item.box.site}</Text>
          </>
        )}

        <View style={styles.contractFooter}>
          <View>
            <Text style={styles.label}>Montant mensuel</Text>
            <Text style={styles.price}>{item.total_monthly_amount?.toFixed(2)} €</Text>
          </View>
          <View>
            <Text style={styles.label}>Début</Text>
            <Text style={styles.date}>
              {new Date(item.start_date).toLocaleDateString('fr-FR')}
            </Text>
          </View>
        </View>
      </Card>
    </TouchableOpacity>
  );

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <FlatList
        data={contracts}
        renderItem={renderContract}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          <Card>
            <Text style={styles.emptyText}>Aucun contrat</Text>
          </Card>
        }
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  listContent: {
    padding: 16,
  },
  contractHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  contractNumber: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.secondary,
  },
  boxNumber: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  boxDetails: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 4,
  },
  siteInfo: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 12,
  },
  contractFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: COLORS.divider,
    paddingTop: 12,
  },
  label: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginBottom: 4,
  },
  price: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  date: {
    fontSize: 14,
    color: COLORS.text.primary,
  },
  emptyText: {
    textAlign: 'center',
    color: COLORS.text.secondary,
    fontSize: 14,
  },
});

export default ContractsScreen;
